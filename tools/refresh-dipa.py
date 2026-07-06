#!/usr/bin/env python3
"""
Auto-refresh Realisasi Anggaran DIPA 01 & 03 dari Google Drive folder.

Cara kerja:
  1. List file PDF di folder Gdrive (public) via embeddedfolderview.
  2. Cari PDF TERBARU (sort by bulan + tahun di filename).
  3. Download → parse text → extract DIPA 01 & 03 (%, pagu, realisasi).
  4. Generate donut chart HTML (CSS conic-gradient).
  5. Update module Joomla (DB) dengan data terbaru.

Jalankan saat ada PDF baru:
    MYSQL_BIN=/path/to/mysql python tools/refresh-dipa.py
"""

import urllib.request
import re
import os
import ssl
import sys
import subprocess
import pymupdf

# ====== KONFIGURASI ======
FOLDER_ID = '1fVI4UvO54g9u4jdIEjM9EgGGZOS0igNV'
MODULE_ID = 816  # Modul "Kinerja & Akuntabilitas" — blok .dipa-widget di dalamnya yang di-update
MYSQL_BIN = os.environ.get('MYSQL_BIN', 'mysql')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS', '')
DB_NAME = os.environ.get('DB_NAME', 'pn_natuna_rebuild')
# ==========================

SSL_CTX = ssl.create_default_context()
SSL_CTX.check_hostname = False
SSL_CTX.verify_mode = ssl.CERT_NONE

MONTHS = {
    'januari': 1, 'februari': 2, 'maret': 3, 'april': 4, 'mei': 5, 'juni': 6,
    'juli': 7, 'agustus': 8, 'september': 9, 'oktober': 10, 'november': 11, 'desember': 12,
    'jan': 1, 'feb': 2, 'mar': 3, 'apr': 4, 'jun': 6, 'jul': 7, 'agu': 8, 'sep': 9,
    'okt': 10, 'nov': 11, 'des': 12,
}


def fetch(url):
    req = urllib.request.Request(url, headers={
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    })
    return urllib.request.urlopen(req, context=SSL_CTX, timeout=30).read()


def list_folder(folder_id):
    url = f'https://drive.google.com/embeddedfolderview?id={folder_id}#list'
    html = fetch(url).decode('utf-8', 'ignore')
    ids = re.findall(r'/file/d/([a-zA-Z0-9_-]+)', html)
    names = re.findall(r'flip-entry-title">([^<]+)</div>', html)
    return list(zip(ids, names))


def parse_period(name):
    """Extract (year, month) from filename untuk sorting. Prefer file dengan '01 dan 03'."""
    low = name.lower()
    year_m = re.search(r'(20\d{2})', low)
    year = int(year_m.group(1)) if year_m else 0
    month = 0
    for mname, mnum in MONTHS.items():
        if re.search(r'\b' + mname + r'\b', low):
            month = mnum
            break
    has_both = '01' in low and '03' in low  # prefer combined DIPA 01+03 report
    return (year, month, has_both)


def find_latest(files):
    """Cari PDF terbaru: prefer combined '01 dan 03', sort by year+month desc."""
    candidates = []
    for fid, name in files:
        if not name.lower().endswith('.pdf'):
            continue
        if 'dipa' not in name.lower() and 'realisasi' not in name.lower() and 'lra' not in name.lower():
            continue
        year, month, has_both = parse_period(name)
        candidates.append({
            'id': fid, 'name': name, 'year': year, 'month': month, 'has_both': has_both
        })
    if not candidates:
        return None
    # sort: has_both first, then year+month desc
    candidates.sort(key=lambda x: (x['has_both'], x['year'], x['month']), reverse=True)
    return candidates[0]


def gdrive_download(file_id, dest):
    url = f'https://drive.google.com/uc?export=download&id={file_id}'
    data = fetch(url)
    if data[:1] in (b'<',) and b'confirm' in data:
        m = re.search(rb'confirm=([a-zA-Z0-9_-]+)', data)
        if m:
            data = fetch(f'https://drive.google.com/uc?export=download&id={file_id}&confirm={m.group(1).decode()}')
    with open(dest, 'wb') as f:
        f.write(data)
    return len(data)


def parse_dipa(pdf_path):
    """Extract DIPA 01 & 03 data (pct, pagu, realisasi) dari PDF."""
    doc = pymupdf.open(pdf_path)
    full = '\n'.join(p.get_text() for p in doc)
    doc.close()
    norm = re.sub(r'\s+', ' ', full)

    results = {}
    for unit in ['01', '03']:
        idx = norm.find(f'{unit} Unit Organisasi')
        if idx < 0:
            continue
        section = norm[idx:idx + 600]
        jm = section.find('JUMLAH SELURUHNYA')
        if jm < 0:
            continue
        chunk = section[max(0, jm - 120):jm + 120]
        pcts = re.findall(r'(\d{1,2}\.\d{1,2})\s*%', chunk)
        after = chunk.split('JUMLAH SELURUHNYA', 1)[1] if 'JUMLAH SELURUHNYA' in chunk else ''
        nums = re.findall(r'(\d[\d,]*)', after)
        nums_int = [int(n.replace(',', '')) for n in nums if len(n.replace(',', '')) > 3]
        pagu = max(nums_int) if nums_int else 0
        pct = float(pcts[0]) if pcts else 0.0
        realisasi = round(pagu * pct / 100)
        results[unit] = {'pct': pct, 'pagu': pagu, 'realisasi': realisasi}
    return results


def format_rp(amount):
    """Format rupiah: miliar / juta / ribu."""
    if amount >= 1_000_000_000:
        return f'Rp {amount / 1_000_000_000:.2f} miliar'
    if amount >= 1_000_000:
        return f'Rp {amount / 1_000_000:.2f} juta'
    return f'Rp {amount:,}'


def build_html(data, period_label, file_id=''):
    """Generate donut chart HTML."""
    colors = {'01': '#1f5b4b', '03': '#8f1f0b'}
    labels = {'01': 'DIPA 01', '03': 'DIPA 03'}
    items = []
    for unit in ['01', '03']:
        if unit not in data:
            continue
        d = data[unit]
        pct_str = f'{d["pct"]:.2f}'.replace('.', ',')
        items.append(
            f'<div class="dipa-item">'
            f'<div class="dipa-ring" style="--pct:{d["pct"]};--dipa-color:{colors[unit]};">'
            f'<span class="dipa-ring-pct">{pct_str}%</span></div>'
            f'<span class="dipa-label">{labels[unit]}</span>'
            f'<span class="dipa-amount">{format_rp(d["pagu"])}</span>'
            f'<span class="dipa-sub">terserap {format_rp(d["realisasi"])}</span>'
            f'</div>'
        )
    gdrive_url = f'https://drive.google.com/file/d/{file_id}/view' if file_id else '#'
    return (
        f'<div class="dipa-widget">'
        f'<div class="dipa-subhead">Realisasi Anggaran DIPA</div>'
        f'<div class="dipa-period">Periode {period_label}</div>'
        f'<a class="dipa-link" href="{gdrive_url}" target="_blank" rel="noopener" title="Buka laporan PDF">'
        f'<div class="dipa-grid">{"".join(items)}</div>'
        f'<span class="dipa-link-hint">Klik untuk lihat laporan PDF</span>'
        f'</a>'
        f'</div>'
    )


def run_mysql(sql):
    cmd = [MYSQL_BIN, '-u', DB_USER]
    if DB_PASS:
        cmd += [f'-p{DB_PASS}']
    cmd += ['--default-character-set=utf8mb4', '-N', '-B', DB_NAME]
    return subprocess.run(cmd, input=sql, capture_output=True, text=True, encoding='utf-8')


def update_module_db(widget_html):
    """Replace hanya blok <div class="dipa-widget">...</div> di akhir konten modul.

    Modul 816 berisi skor SKM/IPAK + widget DIPA; jangan timpa seluruh konten.
    """
    result = run_mysql(f'SELECT content FROM pnn_modules WHERE id = {MODULE_ID};')
    if result.returncode != 0:
        return False, result.stderr
    current = result.stdout.rstrip('\n').replace('\\n', '\n')
    if '<div class="dipa-widget">' in current:
        new_content = re.sub(r'<div class="dipa-widget">.*$', widget_html, current, flags=re.S)
    else:
        new_content = current + widget_html
    escaped = new_content.replace('\\', '\\\\').replace("'", "\\'")
    result = run_mysql(f"UPDATE pnn_modules SET content = '{escaped}' WHERE id = {MODULE_ID};")
    return result.returncode == 0, result.stderr


def main():
    print('[1/4] List file dari Google Drive folder...')
    files = list_folder(FOLDER_ID)
    print(f'      Ditemukan {len(files)} file:')
    for fid, name in files:
        print(f'        - {name}')

    print('\n[2/4] Cari PDF terbaru...')
    latest = find_latest(files)
    if not latest:
        print('[ERROR] Tidak ada PDF DIPA ditemukan.')
        sys.exit(1)
    print(f'      Terbaru: {latest["name"]} ({latest["year"]}-{latest["month"]:02d})')

    print('\n[3/4] Download & parse PDF...')
    tmp_pdf = os.path.join(os.path.dirname(__file__), '_dipa_latest.pdf')
    sz = gdrive_download(latest['id'], tmp_pdf)
    print(f'      Downloaded {sz} bytes')
    data = parse_dipa(tmp_pdf)
    os.remove(tmp_pdf)
    for unit, d in data.items():
        print(f'      DIPA {unit}: {d["pct"]:.2f}% | pagu {format_rp(d["pagu"])} | realisasi {format_rp(d["realisasi"])}')

    if not data:
        print('[ERROR] Gagal parse data DIPA.')
        sys.exit(1)

    # period label dari filename
    period_label = latest['name'].replace('.pdf', '').replace('_', ' ')
    # extract just the month+year part
    pm = re.search(r'(\w+\s+\d{4})', latest['name'])
    if pm:
        period_label = pm.group(1)

    print('\n[4/4] Update module Joomla (DB)...')
    html = build_html(data, period_label, latest['id'])
    ok, err = update_module_db(html)
    if ok:
        print('      Module updated.')
    else:
        print(f'      [ERROR] DB: {err}')

    print(f'\nSELESAI. DIPA widget menampilkan data {period_label}.')


if __name__ == '__main__':
    main()
