#!/usr/bin/env python3
"""
Auto-refresh Indeks Survei (SKM + IPAK) dari Google Drive folder.

Cara kerja:
  1. List file di folder Gdrive (public) via embeddedfolderview.
  2. Cari file TERBARU per jenis (SKM, IPAK) berdasarkan TW + tahun.
  3. Download PDF → convert ke PNG (PyMuPDF) → resize web.
  4. Update module Joomla (DB) dengan path + tag terbaru.

Jalankan saat ada PDF baru di Gdrive:
    python tools/refresh-survey.py

Prasyarat:
    pip install PyMuPDF Pillow
    (MySQL CLI harus ter-install untuk update DB)
"""

import urllib.request
import re
import os
import ssl
import sys
import subprocess
import pymupdf
from PIL import Image

# ====== KONFIGURASI ======
FOLDER_ID = '1XVTZjSGKPzM0XPSTlYg4w7f6Ut-QyG7z'
SURVEY_TYPES = ['SKM', 'IPAK']
SURVEY_LABELS = {
    'SKM': 'Indeks Kepuasan Masyarakat (IKM)',
    'IPAK': 'Indeks Persepsi Anti Korupsi (IPAK)',
}
OUT_DIR = os.path.join(os.path.dirname(__file__), '..', 'images', 'surveys')
IMG_WIDTH = 800  # web display width (retina)
MODULE_ID = 816  # Joomla module id untuk survey card

# MySQL CLI path (sesuaikan environment)
MYSQL_BIN = os.environ.get('MYSQL_BIN', 'mysql')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS', '')
DB_NAME = os.environ.get('DB_NAME', 'pn_natuna_rebuild')
# ==========================

SSL_CTX = ssl.create_default_context()
SSL_CTX.check_hostname = False
SSL_CTX.verify_mode = ssl.CERT_NONE


def fetch(url):
    req = urllib.request.Request(url, headers={
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    })
    return urllib.request.urlopen(req, context=SSL_CTX, timeout=30).read()


def list_folder(folder_id):
    """List file (id, name) dari public Google Drive folder."""
    url = f'https://drive.google.com/embeddedfolderview?id={folder_id}#list'
    html = fetch(url).decode('utf-8', 'ignore')
    ids = re.findall(r'/file/d/([a-zA-Z0-9_-]+)', html)
    names = re.findall(r'flip-entry-title">([^<]+)</div>', html)
    return list(zip(ids, names))


def find_latest(files, survey_type):
    """Cari file terbaru untuk survey_type berdasarkan TW + tahun."""
    candidates = []
    for fid, name in files:
        m = re.match(rf'^{re.escape(survey_type)}\s+TW(\d)\s+(\d{{4}})', name, re.I)
        if m:
            candidates.append({
                'year': int(m.group(2)),
                'tw': int(m.group(1)),
                'id': fid,
                'name': name,
            })
    if not candidates:
        return None
    candidates.sort(key=lambda x: (x['year'], x['tw']), reverse=True)
    return candidates[0]


def gdrive_download(file_id, dest):
    """Download file dari Google Drive (handle confirm token untuk file besar)."""
    url = f'https://drive.google.com/uc?export=download&id={file_id}'
    data = fetch(url)
    if data[:1] in (b'<', b'') and b'confirm' in data:
        m = re.search(rb'confirm=([a-zA-Z0-9_-]+)', data)
        if m:
            url2 = f'https://drive.google.com/uc?export=download&id={file_id}&confirm={m.group(1).decode()}'
            data = fetch(url2)
    with open(dest, 'wb') as f:
        f.write(data)
    return len(data)


def convert_pdf_to_png(pdf_path, png_path, width):
    """Convert halaman pertama PDF ke PNG, resize ke width tertentu."""
    doc = pymupdf.open(pdf_path)
    pix = doc[0].get_pixmap(dpi=200)
    tmp = png_path + '.raw.png'
    pix.save(tmp)
    doc.close()
    im = Image.open(tmp)
    ratio = width / im.width
    im = im.resize((width, round(im.height * ratio)), Image.LANCZOS)
    im.save(png_path, optimize=True)
    os.remove(tmp)
    return png_path


def build_module_content(latest):
    """Build HTML carousel content untuk Joomla module survey."""
    slides = []
    dots = []
    first_label = ''
    for idx, stype in enumerate(SURVEY_TYPES):
        info = latest.get(stype)
        if not info:
            continue
        img = f'/images/surveys/{stype}_TW{info["tw"]}_{info["year"]}.png'
        base = SURVEY_LABELS.get(stype, stype)
        tag = f'TW{info["tw"]} {info["year"]}'
        full_label = f'{base} &mdash; {tag}'
        if not first_label:
            first_label = full_label
        active = ' is-active' if idx == 0 else ''
        short = 'IKM' if stype == 'SKM' else stype
        slides.append(
            f'<div class="survey-slide{active}" data-label="{full_label}">'
            f'<a href="{img}" target="_blank" rel="noopener">'
            f'<img src="{img}" alt="{base} {tag}" loading="lazy"></a>'
            f'</div>'
        )
        dots.append(
            f'<button type="button" data-survey-slide="{idx}"{active} aria-label="{short}"></button>'
        )
    return (
        '<h2>Indeks Pelayanan Publik</h2>\n'
        '<div class="survey-carousel" data-interval="5000">\n'
        '<div class="survey-carousel-viewport">\n'
 + '\n'.join(slides) + '\n'
        '</div>\n'
        f'<div class="survey-caption">{first_label}</div>\n'
        '<div class="survey-carousel-dots">\n'
 + '\n'.join(dots) + '\n'
        '</div>\n'
        '</div>'
    )


def update_module_db(content):
    """Update Joomla module content via MySQL CLI."""
    sql_file = os.path.join(os.path.dirname(__file__), '_survey_update.sql')
    # Escape single quotes untuk SQL
    escaped = content.replace("\\", "\\\\").replace("'", "\\'")
    with open(sql_file, 'w', encoding='utf-8') as f:
        f.write(f"UPDATE pnn_modules SET content = '{escaped}' WHERE id = {MODULE_ID};\n")
    cmd = [MYSQL_BIN, '-u', DB_USER]
    if DB_PASS:
        cmd += [f'-p{DB_PASS}']
    cmd += ['--default-character-set=utf8mb4', DB_NAME]
    with open(sql_file, 'r', encoding='utf-8') as f:
        result = subprocess.run(cmd, stdin=f, capture_output=True, text=True)
    os.remove(sql_file)
    if result.returncode != 0:
        print(f'[ERROR] MySQL: {result.stderr}')
        return False
    return True


def main():
    os.makedirs(OUT_DIR, exist_ok=True)

    print('[1/4] List file dari Google Drive folder...')
    files = list_folder(FOLDER_ID)
    print(f'      Ditemukan {len(files)} file:')
    for fid, name in files:
        print(f'        - {name}')

    print('\n[2/4] Cari file terbaru per jenis survei...')
    latest = {}
    for stype in SURVEY_TYPES:
        info = find_latest(files, stype)
        if info:
            print(f'      {stype}: TW{info["tw"]} {info["year"]} ({info["name"]})')
            latest[stype] = info
        else:
            print(f'      {stype}: TIDAK DITEMUKAN')

    if not latest:
        print('\n[ERROR] Tidak ada file survei ditemukan. Cek FOLDER_ID & naming.')
        sys.exit(1)

    print('\n[3/4] Download & convert PDF -> PNG...')
    for stype, info in latest.items():
        pdf_path = os.path.join(OUT_DIR, f'{stype}_TW{info["tw"]}_{info["year"]}.pdf')
        png_path = os.path.join(OUT_DIR, f'{stype}_TW{info["tw"]}_{info["year"]}.png')
        if os.path.exists(png_path):
            print(f'      {stype}: sudah ada, skip ({os.path.basename(png_path)})')
            continue
        print(f'      {stype}: download...')
        sz = gdrive_download(info['id'], pdf_path)
        print(f'        PDF: {sz} bytes')
        convert_pdf_to_png(pdf_path, png_path, IMG_WIDTH)
        print(f'        PNG: {os.path.getsize(png_path) // 1024}KB -> {os.path.basename(png_path)}')

    print('\n[4/4] Update module Joomla (DB)...')
    content = build_module_content(latest)
    if update_module_db(content):
        print('      Module updated.')
    else:
        print('      [WARN] DB update gagal — image sudah ter-convert, update module manual.')

    print('\nSELESAI. Survey card sekarang menampilkan data terbaru.')


if __name__ == '__main__':
    main()
