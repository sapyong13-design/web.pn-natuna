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

import html
import json
import os
import re
import ssl
import subprocess
import sys
import tempfile
import urllib.parse
import urllib.request

import pymupdf

# ====== KONFIGURASI ======
FOLDER_ID = '1fVI4UvO54g9u4jdIEjM9EgGGZOS0igNV'
MODULE_ID = 816  # Modul "Kinerja & Akuntabilitas" — blok .dipa-widget di dalamnya yang di-update
# Satu-satunya penanda blok widget di konten modul. build_html() menulis tag ini
# dan update_module_db() mencarinya lewat pola yang sama, jadi keduanya tidak
# bisa berbeda diam-diam. Atribut apa pun setelah class ikut cocok: menambahkan
# `data-dipa-board` pernah membuat pencocokan literal gagal, dan akibatnya tiap
# refresh menambah salinan widget alih-alih menggantinya.
WIDGET_OPEN = '<div class="dipa-widget" data-dipa-board>'
WIDGET_OPEN_RE = re.compile(r'<div class="dipa-widget"[^>]*>')
MYSQL_BIN = os.environ.get('MYSQL_BIN', 'mysql')
MYSQL_DEFAULTS_FILE = os.environ.get('MYSQL_DEFAULTS_FILE', '')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS', '')
DB_NAME = os.environ.get('DB_NAME', 'pn_natuna_rebuild')
# Berapa periode terakhir yang ditawarkan di pemilih kartu.
MAX_PERIODS = 12
# Hasil parse tiap PDF disimpan per file id supaya cron tidak mengunduh ulang
# seluruh folder tiap kali jalan; PDF di Drive tidak pernah berubah isinya.
CACHE_PATH = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    'cache', 'pn_natuna_dipa_periods.json',
)
# ==========================
MAX_HTML_BYTES = 2 * 1024 * 1024
MAX_PDF_BYTES = 25 * 1024 * 1024
ALLOWED_HOSTS = frozenset({'drive.google.com', 'drive.usercontent.google.com', 'docs.google.com'})

SSL_CTX = ssl.create_default_context()

MONTHS = {
    'januari': 1, 'februari': 2, 'maret': 3, 'april': 4, 'mei': 5, 'juni': 6,
    'juli': 7, 'agustus': 8, 'september': 9, 'oktober': 10, 'november': 11, 'desember': 12,
    'jan': 1, 'feb': 2, 'mar': 3, 'apr': 4, 'jun': 6, 'jul': 7, 'agu': 8, 'sep': 9,
    'okt': 10, 'nov': 11, 'des': 12,
}


class SafeRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        parsed = urllib.parse.urlsplit(newurl)
        host = (parsed.hostname or '').lower()
        if parsed.scheme != 'https' or host not in ALLOWED_HOSTS:
            raise ValueError('Redirect HTTPS/host tidak diizinkan')
        return super().redirect_request(req, fp, code, msg, headers, newurl)


OPENER = urllib.request.build_opener(
    urllib.request.HTTPSHandler(context=SSL_CTX), SafeRedirectHandler()
)


def fetch(url, max_bytes=MAX_HTML_BYTES, allowed_mimes=()):
    parsed = urllib.parse.urlsplit(url)
    if parsed.scheme != 'https' or (parsed.hostname or '').lower() not in ALLOWED_HOSTS:
        raise ValueError('URL unduhan tidak diizinkan')
    req = urllib.request.Request(url, headers={
        'User-Agent': 'PN-Natuna secure refresh/1.0',
        'Accept-Encoding': 'identity',
    })
    with OPENER.open(req, timeout=30) as response:
        mime = response.headers.get_content_type().lower()
        if allowed_mimes and mime not in allowed_mimes:
            raise ValueError('Tipe konten unduhan tidak valid')
        declared = response.headers.get('Content-Length')
        if declared and int(declared) > max_bytes:
            raise ValueError('Unduhan terlalu besar')
        data = response.read(max_bytes + 1)
    if len(data) > max_bytes:
        raise ValueError('Unduhan terlalu besar')
    return data


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


def collect_periods(files):
    """Semua periode DIPA di folder, terbaru lebih dulu, maksimum MAX_PERIODS.

    Satu bulan bisa punya lebih dari satu berkas (laporan 01 sendiri, 03 sendiri,
    lalu gabungan). Yang dipakai satu per bulan: gabungan `01 dan 03` menang
    karena hanya berkas itu memuat kedua unit organisasi.
    """
    best = {}
    for fid, name in files:
        low = name.lower()
        if not low.endswith('.pdf'):
            continue
        if 'dipa' not in low and 'realisasi' not in low and 'lra' not in low:
            continue
        year, month, has_both = parse_period(name)
        if not year or not month:
            continue
        key = (year, month)
        current = best.get(key)
        if current is None or (has_both, name) > (current['has_both'], current['name']):
            best[key] = {'id': fid, 'name': name, 'year': year, 'month': month, 'has_both': has_both}
    ordered = sorted(best.values(), key=lambda x: (x['year'], x['month']), reverse=True)
    return ordered[:MAX_PERIODS]


def load_cache():
    try:
        with open(CACHE_PATH, encoding='utf-8') as handle:
            cached = json.load(handle)
        return cached if isinstance(cached, dict) else {}
    except (OSError, ValueError):
        return {}


def save_cache(cache):
    """Tulis atomik supaya cron yang terputus tidak meninggalkan JSON separuh."""
    directory = os.path.dirname(CACHE_PATH)
    try:
        os.makedirs(directory, exist_ok=True)
        fd, staged = tempfile.mkstemp(prefix='.dipa-cache-', suffix='.json', dir=directory)
        with os.fdopen(fd, 'w', encoding='utf-8') as handle:
            json.dump(cache, handle, ensure_ascii=False)
        os.replace(staged, CACHE_PATH)
    except OSError as exc:
        print(f'      [WARN] Cache periode tidak tersimpan: {exc}', file=sys.stderr)


def period_label(entry):
    names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
    return f'{names[entry["month"]]} {entry["year"]}'


def period_short(entry):
    names = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
             'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    return f'{names[entry["month"]]} {str(entry["year"])[2:]}'


def attach_deltas(periods):
    """Isi `delta` tiap unit: selisih poin persentase terhadap bulan sebelumnya.

    Dihitung dari urutan periode yang benar-benar tersedia, bukan kalender. Bila
    Mei tidak ada di folder, pembanding Juni adalah April - dan itu dinyatakan
    lewat `delta_vs` supaya kartunya tidak berbohong soal apa yang dibandingkan.
    """
    ascending = sorted(periods, key=lambda x: (x['year'], x['month']))
    for index, entry in enumerate(ascending):
        previous = ascending[index - 1] if index else None
        for unit, values in entry.get('data', {}).items():
            prior = (previous or {}).get('data', {}).get(unit) if previous else None
            if prior is None:
                values['delta'] = None
                values['delta_vs'] = ''
                continue
            values['delta'] = values['pct'] - prior['pct']
            values['delta_vs'] = period_label(previous)
    return periods


def gdrive_download(file_id, dest):
    url = f'https://drive.google.com/uc?export=download&id={file_id}'
    data = fetch(url, MAX_PDF_BYTES, ('application/pdf', 'application/octet-stream', 'text/html'))
    if data[:1] == b'<' and b'confirm' in data:
        m = re.search(rb'confirm=([a-zA-Z0-9_-]+)', data)
        if m:
            data = fetch(f'https://drive.google.com/uc?export=download&id={file_id}&confirm={m.group(1).decode()}', MAX_PDF_BYTES, ('application/pdf', 'application/octet-stream'))
    if not data.startswith(b'%PDF-'):
        raise ValueError('Berkas unduhan bukan PDF')
    directory = os.path.dirname(os.path.abspath(dest))
    fd, staged = tempfile.mkstemp(prefix='.dipa-', suffix='.pdf', dir=directory)
    try:
        with os.fdopen(fd, 'wb') as f:
            f.write(data)
            f.flush()
            os.fsync(f.fileno())
        os.replace(staged, dest)
    except BaseException:
        try:
            os.remove(staged)
        except FileNotFoundError:
            pass
        raise
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


def format_delta(delta):
    """Badge kenaikan poin persentase terhadap bulan pembanding.

    Serapan itu kumulatif, jadi arah normalnya naik. Penurunan tetap
    ditangani - kalau muncul, itu justru tanda data perlu diperiksa, bukan
    sesuatu yang boleh disembunyikan.
    """
    if delta is None:
        return None, '', ''
    value = f'{abs(delta):.2f}'.replace('.', ',')
    if delta > 0.005:
        return 'is-up', '&#9650;', f'+{value}'
    if delta < -0.005:
        return 'is-down', '&#9660;', f'-{value}'
    return 'is-flat', '&#8213;', value


def build_panel(entry, colors, labels):
    label_text = html.escape(period_label(entry), quote=True)
    gdrive_url = html.escape(
        f'https://drive.google.com/file/d/{entry["id"]}/view' if entry.get('id') else '#', quote=True
    )
    items = []
    for unit in ['01', '03']:
        values = entry.get('data', {}).get(unit)
        if not values:
            continue
        pct_str = f'{values["pct"]:.2f}'.replace('.', ',')
        state, glyph, amount = format_delta(values.get('delta'))
        if state:
            vs = html.escape(str(values.get('delta_vs') or ''), quote=True)
            title = f'Selisih {amount} poin dibanding {vs}' if vs else f'Selisih {amount} poin'
            delta_html = (
                f'<span class="dipa-delta {state}" title="{html.escape(title, quote=True)}">'
                f'<span aria-hidden="true">{glyph}</span> {amount} poin</span>'
            )
        else:
            delta_html = '<span class="dipa-delta is-none">periode awal</span>'
        items.append(
            f'<div class="dipa-item">'
            f'<div class="dipa-ring" style="--pct:{values["pct"]};--dipa-color:{colors[unit]};">'
            f'<span class="dipa-ring-pct">{pct_str}%</span></div>'
            f'<span class="dipa-label">{labels[unit]}</span>'
            f'{delta_html}'
            f'<span class="dipa-amount">{format_rp(values["pagu"])}</span>'
            f'<span class="dipa-sub">terserap {format_rp(values["realisasi"])}</span>'
            f'</div>'
        )
    return (
        f'<div class="dipa-period">Periode {label_text}</div>'
        f'<a class="dipa-link" href="{gdrive_url}" target="_blank" rel="noopener" '
        f'title="Buka laporan PDF {label_text}">'
        f'<div class="dipa-grid">{"".join(items)}</div>'
        f'<span class="dipa-link-hint">Klik untuk lihat laporan PDF</span>'
        f'</a>'
    )


def build_html(periods):
    """Kartu DIPA dengan pemilih periode.

    Panel periode teraktif dirender `is-active` dari server, jadi kartunya sudah
    benar sebelum JS jalan - tanpa JS pemilihnya tidak berpindah, tapi angkanya
    tetap tampil. Ini pola yang sama dengan tab Kabar Instansi.
    """
    colors = {'01': '#1f5b4b', '03': '#8f1f0b'}
    labels = {'01': 'DIPA 01', '03': 'DIPA 03'}
    usable = [p for p in periods if p.get('data')]
    if not usable:
        return ''
    tabs, panels = [], []
    for index, entry in enumerate(usable):
        active = index == 0
        slug = f'{entry["year"]}-{entry["month"]:02d}'
        tabs.append(
            f'<button type="button" role="tab" id="dipa-tab-{slug}" data-dipa-tab="{slug}"'
            f' aria-controls="dipa-panel-{slug}" aria-selected="{"true" if active else "false"}"'
            f' tabindex="{0 if active else -1}"'
            f' class="dipa-tab{" is-active" if active else ""}">{html.escape(period_short(entry), quote=True)}</button>'
        )
        panels.append(
            f'<div class="dipa-panel{" is-active" if active else ""}" id="dipa-panel-{slug}"'
            f' role="tabpanel" aria-labelledby="dipa-tab-{slug}"{"" if active else " hidden"}>'
            f'{build_panel(entry, colors, labels)}</div>'
        )
    picker = ''
    if len(usable) > 1:
        picker = (
            f'<div class="dipa-tabs" role="tablist" aria-label="Pilih periode laporan DIPA">'
            f'{"".join(tabs)}</div>'
        )
    return (
        f'{WIDGET_OPEN}'
        f'<div class="dipa-subhead">Realisasi Anggaran DIPA</div>'
        f'{picker}'
        f'<div class="dipa-panels">{"".join(panels)}</div>'
        f'</div>'
    )


def run_mysql(sql):
    cmd = [MYSQL_BIN]
    if MYSQL_DEFAULTS_FILE:
        cmd.append(f'--defaults-extra-file={MYSQL_DEFAULTS_FILE}')
    else:
        cmd += ['-u', DB_USER]
        if DB_PASS:
            cmd += [f'-p{DB_PASS}']
    cmd += ['--default-character-set=utf8mb4', '-N', '-B', DB_NAME]
    return subprocess.run(cmd, input=sql, capture_output=True, text=True, encoding='utf-8')


def update_module_db(widget_html):
    """Ganti blok widget DIPA di konten modul; bagian lain jangan disentuh.

    Modul 816 memuat skor SKM/IPAK lalu widget DIPA sebagai blok terakhir, jadi
    penggantian dilakukan dengan memotong dari tag pembuka widget sampai ujung
    konten. Pencocokannya lewat WIDGET_OPEN_RE supaya perubahan atribut pada tag
    pembuka tidak diam-diam berubah jadi penambahan salinan kedua.
    """
    result = run_mysql(f'SELECT content FROM pnn_modules WHERE id = {MODULE_ID};')
    if result.returncode != 0:
        return False, result.stderr
    current = result.stdout.rstrip('\n').replace('\\n', '\n')
    found = WIDGET_OPEN_RE.findall(current)
    if len(found) > 1:
        return False, (f'Konten modul {MODULE_ID} memuat {len(found)} blok .dipa-widget. '
                       'Rapikan manual dulu; refresh menolak menebak mana yang benar.')
    if found:
        new_content = current[:WIDGET_OPEN_RE.search(current).start()] + widget_html
    else:
        new_content = current + widget_html
    if len(WIDGET_OPEN_RE.findall(new_content)) != 1:
        return False, 'Hasil penggantian tidak menyisakan tepat satu blok .dipa-widget.'
    escaped = new_content.replace('\\', '\\\\').replace("'", "\\'")
    result = run_mysql(f"UPDATE pnn_modules SET content = '{escaped}' WHERE id = {MODULE_ID};")
    return result.returncode == 0, result.stderr


def resolve_periods(entries, cache):
    """Parse tiap periode, pakai cache bila file id-nya sudah pernah diparse.

    PDF di Drive tidak pernah berubah isinya, jadi hasil parse boleh disimpan
    permanen per file id. Satu berkas gagal tidak boleh menjatuhkan seluruh
    refresh - periode itu saja yang dilewati.
    """
    resolved, fetched = [], 0
    for entry in entries:
        cached = cache.get(entry['id'])
        if isinstance(cached, dict) and cached.get('data'):
            entry['data'] = {
                unit: dict(values) for unit, values in cached['data'].items()
            }
            resolved.append(entry)
            continue
        tmp_pdf = os.path.join(os.path.dirname(__file__), f'_dipa_{entry["id"][:12]}.pdf')
        try:
            size = gdrive_download(entry['id'], tmp_pdf)
            data = parse_dipa(tmp_pdf)
        except Exception as exc:
            print(f'      [WARN] {entry["name"]} dilewati: {exc}', file=sys.stderr)
            continue
        finally:
            if os.path.exists(tmp_pdf):
                os.remove(tmp_pdf)
        if not data:
            print(f'      [WARN] {entry["name"]} tidak menghasilkan data DIPA.', file=sys.stderr)
            continue
        fetched += 1
        entry['data'] = data
        cache[entry['id']] = {'name': entry['name'], 'data': data}
        resolved.append(entry)
        print(f'      + {period_label(entry)} ({size} byte)')
    return resolved, fetched


def main():
    print('[1/4] List file dari Google Drive folder...')
    files = list_folder(FOLDER_ID)
    print(f'      Ditemukan {len(files)} file.')

    print(f'\n[2/4] Kumpulkan periode (maks {MAX_PERIODS})...')
    entries = collect_periods(files)
    if not entries:
        print('[ERROR] Tidak ada PDF DIPA ditemukan.')
        sys.exit(1)
    for entry in entries:
        print(f'        - {period_label(entry)}  {entry["name"]}')

    print('\n[3/4] Parse PDF (cache dipakai bila ada)...')
    cache = load_cache()
    periods, fetched = resolve_periods(entries, cache)
    if not periods:
        print('[ERROR] Tidak ada periode yang berhasil diparse.')
        sys.exit(1)
    save_cache(cache)
    print(f'      {len(periods)} periode siap, {fetched} diunduh baru, {len(periods) - fetched} dari cache.')

    attach_deltas(periods)
    for entry in periods:
        for unit, values in sorted(entry.get('data', {}).items()):
            delta = values.get('delta')
            trend = 'awal' if delta is None else f'{delta:+.2f} poin vs {values.get("delta_vs")}'
            print(f'      {period_label(entry):16} DIPA {unit}: {values["pct"]:6.2f}%  {trend}')

    print('\n[4/4] Update module Joomla (DB)...')
    markup = build_html(periods)
    if not markup:
        print('[ERROR] Widget kosong, DB tidak diubah.')
        sys.exit(1)
    ok, err = update_module_db(markup)
    if ok:
        print('      Module updated.')
    else:
        print(f'      [ERROR] Pembaruan database gagal: {err}', file=sys.stderr)
        sys.exit(1)

    print(f'\nSELESAI. Kartu DIPA menawarkan {len(periods)} periode, teraktif {period_label(periods[0])}.')


if __name__ == '__main__':
    try:
        main()
    except Exception:
        print('[ERROR] Refresh DIPA gagal.', file=sys.stderr)
        sys.exit(1)
