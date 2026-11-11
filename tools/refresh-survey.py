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

import html
import os
import re
import ssl
import subprocess
import sys
import tempfile
import urllib.parse
import urllib.request

import pymupdf
from PIL import Image

# ====== KONFIGURASI ======
FOLDER_ID = '1XVTZjSGKPzM0XPSTlYg4w7f6Ut-QyG7z'
SURVEY_TYPES = ['SKM', 'IPAK']
SURVEY_LABELS = {
    'SKM': 'Indeks Kepuasan Masyarakat (IKM)',
    'IPAK': 'Indeks Persepsi Anti Korupsi (IPAK)',
}
JPATH_ROOT = os.path.abspath(os.environ.get(
    'PN_NATUNA_JPATH_ROOT', os.path.join(os.path.dirname(__file__), '..')
))
OUT_DIR = os.path.join(JPATH_ROOT, 'images', 'surveys')
IMG_WIDTH = 800  # web display width (retina)
MODULE_ID = 816  # Joomla module id untuk survey card

# Gunakan MYSQL_DEFAULTS_FILE di produksi agar password tidak masuk command/process list.
MYSQL_BIN = os.environ.get('MYSQL_BIN', 'mysql')
MYSQL_DEFAULTS_FILE = os.environ.get('MYSQL_DEFAULTS_FILE', '')
DB_USER = os.environ.get('DB_USER', 'root')
DB_PASS = os.environ.get('DB_PASS', '')
DB_NAME = os.environ.get('DB_NAME', 'pn_natuna_rebuild')
# ==========================
MAX_HTML_BYTES = 2 * 1024 * 1024
MAX_PDF_BYTES = 25 * 1024 * 1024
ALLOWED_HOSTS = frozenset({'drive.google.com', 'drive.usercontent.google.com', 'docs.google.com'})

SSL_CTX = ssl.create_default_context()


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
        m = re.match(rf'^{re.escape(survey_type)}\s+TW\s*([1-4])\s+(\d{{4}})\b', name, re.I)
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
    data = fetch(url, MAX_PDF_BYTES, ('application/pdf', 'application/octet-stream', 'text/html'))
    if data[:1] in (b'<', b'') and b'confirm' in data:
        m = re.search(rb'confirm=([a-zA-Z0-9_-]+)', data)
        if m:
            url2 = f'https://drive.google.com/uc?export=download&id={file_id}&confirm={m.group(1).decode()}'
            data = fetch(url2, MAX_PDF_BYTES, ('application/pdf', 'application/octet-stream'))
    if not data.startswith(b'%PDF-'):
        raise ValueError('Berkas unduhan bukan PDF')
    directory = os.path.dirname(os.path.abspath(dest))
    fd, staged = tempfile.mkstemp(prefix='.survey-', suffix='.pdf', dir=directory)
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

PREVIEW_WIDTH = 400

def _ink_bands(page, threshold=240, min_gap=8, min_height=4):
    """Baris-baris yang memuat tinta, digabung menjadi rentang.

    Dipakai untuk memotong halaman menjadi bagian yang bermakna: kop + tabel skor di
    atas, blok tanda tangan dan cap di bawah, sementara paragraf penjelas dan footer
    cetak (nomor halaman, "Dipindai dengan CamScanner") dibuang.
    """
    ink = page.point(lambda value: 255 if value < threshold else 0)
    rows = [y for y in range(page.height) if ink.crop((0, y, page.width, y + 1)).getbbox()]
    if not rows:
        return []
    bands = []
    start = previous = rows[0]
    for y in rows[1:]:
        if y - previous > min_gap:
            bands.append((start, previous))
            start = y
        previous = y
    bands.append((start, previous))
    return [band for band in bands if band[1] - band[0] >= min_height]

def _signature_panel(page, bands):
    """Potongan halaman yang berisi blok tanda tangan beserta cap.

    Dua keputusan di sini menentukan hasilnya:

    1. Tinggi: pita tinta tertinggi di paruh bawah halaman adalah blok tanda tangan.
       Pita lain dalam jarak 40px ikut disertakan supaya baris nama yang sesekali
       terpisah (jarak antar-baris lebih lebar dari penggabung pita) tidak terpotong.
    2. Lebar: paragraf penutup memakai sisi kiri dan blok tanda tangan sisi kanan.
       Kolom tinta dikelompokkan, lalu gugus paling kanan dipakai — bukan bounding box
       seluruh baris — sehingga tanda tangan dan cap mengisi panel, bukan tersisa
       setengah bidang kosong plus serpihan teks paragraf.
    """
    body = [(start, end) for start, end in bands if start > page.height * 0.45]
    if not body:
        return None
    start, end = max(body, key=lambda band: band[1] - band[0])
    for band_start, band_end in body:
        if band_start > end and band_start - end <= 40:
            end = band_end
    panel = page.crop((0, max(0, start - 12), page.width, min(page.height, end + 12)))

    ink = panel.convert('L').point(lambda value: 255 if value < 240 else 0)
    box = ink.getbbox()
    if box is None:
        return panel
    columns = [x for x in range(box[0], box[2]) if ink.crop((x, 0, x + 1, panel.height)).getbbox()]
    if not columns:
        return panel
    # Gugus kolom: celah lebih lebar dari 3% lebar halaman memisahkan paragraf dari blok tanda tangan.
    clusters = [[columns[0]]]
    for x in columns[1:]:
        if x - clusters[-1][-1] > panel.width * 0.03:
            clusters.append([x])
        else:
            clusters[-1].append(x)
    left, right = clusters[-1][0], clusters[-1][-1]
    if right - left < panel.width * 0.1:
        return panel
    pad = round(panel.width * 0.015)
    return panel.crop((max(0, left - pad), 0, min(panel.width, right + pad), panel.height))

def build_preview(png_path):
    """Pratinjau dokumen publikasi untuk tile skor di beranda.

    Dua panel: kop + tabel skor (halaman atas) dan blok tanda tangan + cap (halaman
    bawah), dipisah garis tipis. Alasannya praktis — pada lebar kartu 300px, satu
    halaman utuh menyusutkan tanda tangan dan cap menjadi noda tak terbaca, sementara
    keduanya justru bukti keaslian dokumen. Kedua panel memakai lebar penuh sehingga
    tampil dua kali lebih besar daripada versi halaman utuh.

    Template memakai berkas `-preview.webp` bila ada dan jatuh ke PNG asli bila tidak,
    jadi langkah ini penyempurnaan, bukan syarat render.
    """
    preview_path = os.path.splitext(png_path)[0] + '-preview.webp'
    with Image.open(png_path) as source:
        source = source.convert('RGB')
        bands = _ink_bands(source.convert('L'))
        panel = _signature_panel(source, bands)

        # Panel atas: kop + tabel skor. Dipotong pada 55% tinggi halaman, bukan pada pita
        # tinta terakhir sebelum tanda tangan, karena paragraf "Tindak lanjut" akan
        # mendorong panel tanda tangan keluar dari kartu.
        top = source.crop((0, 0, source.width, min(source.height, round(source.height * 0.55))))
        signature = panel if panel is not None else source
        if signature.width > source.width:
            signature = signature.crop((0, 0, source.width, signature.height))

        # Kedua panel diskalakan ke lebar kanvas yang sama (bukan skala seragam), supaya
        # tanda tangan dan cap tetap terbaca pada kartu selebar ~115px. Sumbernya 800px
        # sehingga ini tetap penyusutan, bukan pembesaran yang mengaburkan.
        gap = 16
        top_height = round(top.height * PREVIEW_WIDTH / top.width)
        signature_height = round(signature.height * PREVIEW_WIDTH / signature.width)
        canvas = Image.new('RGB', (PREVIEW_WIDTH, top_height + gap + signature_height), 'white')
        canvas.paste(top.resize((PREVIEW_WIDTH, top_height), Image.LANCZOS), (0, 0))
        separator = round(gap / 2)
        for x in range(0, PREVIEW_WIDTH, 12):
            canvas.paste((222, 216, 204), (x, top_height + separator, min(x + 6, PREVIEW_WIDTH), top_height + separator + 1))
        canvas.paste(signature.resize((PREVIEW_WIDTH, signature_height), Image.LANCZOS), (0, top_height + gap))
        canvas.save(preview_path, 'WEBP', quality=84, method=6)
    return preview_path


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


def update_module_db(latest):
    """Perbarui dokumen/periode survei tanpa menimpa skor atau widget DIPA."""
    result = run_mysql(f'SELECT content FROM pnn_modules WHERE id = {MODULE_ID};')
    if result.returncode != 0:
        return False
    content = result.stdout.rstrip('\n').replace('\\n', '\n')
    for stype, info in latest.items():
        path = f'/images/surveys/{stype}_TW{info["tw"]}_{info["year"]}.png'
        content = re.sub(rf'/images/surveys/{stype}_TW\d_20\d{{2}}\.png', path, content)
    periods = {(info['tw'], info['year']) for info in latest.values()}
    if len(periods) == 1:
        tw, year = next(iter(periods))
        content = re.sub(r'TW\d\s+20\d{2}', f'TW{tw} {year}', content)
    escaped = content.replace('\\', '\\\\').replace("'", "\\'")
    result = run_mysql(f"UPDATE pnn_modules SET content = '{escaped}' WHERE id = {MODULE_ID};")
    return result.returncode == 0


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
            # Berkas lama bisa belum punya pratinjau; lengkapi tanpa mengunduh ulang.
            if not os.path.exists(os.path.splitext(png_path)[0] + '-thumb.webp'):
                thumb_path = build_thumb(png_path)
                print(f'        thumb: {os.path.getsize(thumb_path) // 1024}KB -> {os.path.basename(thumb_path)}')
            continue
        print(f'      {stype}: download...')
        sz = gdrive_download(info['id'], pdf_path)
        print(f'        PDF: {sz} bytes')
        convert_pdf_to_png(pdf_path, png_path, IMG_WIDTH)
        print(f'        PNG: {os.path.getsize(png_path) // 1024}KB -> {os.path.basename(png_path)}')
        thumb_path = build_thumb(png_path)
        print(f'        thumb: {os.path.getsize(thumb_path) // 1024}KB -> {os.path.basename(thumb_path)}')

    print('\n[4/4] Update module Joomla (DB)...')
    if update_module_db(latest):
        print('      Module updated.')
    else:
        print('      [WARN] DB update gagal — image sudah ter-convert, update module manual.')

    print('\nSELESAI. Survey card sekarang menampilkan data terbaru.')


if __name__ == '__main__':
    try:
        main()
    except Exception:
        print('[ERROR] Refresh survei gagal.', file=sys.stderr)
        sys.exit(1)
