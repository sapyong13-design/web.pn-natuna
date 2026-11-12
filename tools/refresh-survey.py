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
import json
import os
import re
import ssl
import subprocess
import sys
import tempfile
import urllib.parse
import urllib.request
from datetime import date

import pymupdf
from PIL import Image

# ====== KONFIGURASI ======
# Folder induk publikasi SKM/IPAK. Isinya subfolder per tahun (2026, 2027, ...), bukan
# berkas langsung: publikasi tahun baru cukup diletakkan di subfoldernya sendiri dan
# skrip ini menemukannya tanpa perubahan kode.
CONTAINER_ID = '1oinFzi9Fuvd0aHK5ItycefsP_U-PSYdX'
SECTION_NAME = 'Survei Terbaru'
# Folder induk dibagi dua bagian; seksi ini tinggal di salah satunya.
GROUPS = ('Beranda', 'Transparansi')
SURVEY_TYPES = ['SKM', 'IPAK']
SURVEY_LABELS = {
    'SKM': 'Indeks Kepuasan Masyarakat (SKM)',
    'IPAK': 'Indeks Persepsi Anti Korupsi (IPAK)',
}
JPATH_ROOT = os.path.abspath(os.environ.get(
    'PN_NATUNA_JPATH_ROOT', os.path.join(os.path.dirname(__file__), '..')
))
OUT_DIR = os.path.join(JPATH_ROOT, 'images', 'surveys')
IMG_WIDTH = 800  # web display width (retina)
MODULE_ID = 816  # Modul "Kinerja" (posisi home-survey); hanya berisi kartu skor survei
# Berapa triwulan terakhir yang ditawarkan di pemilih kartu. Delapan = dua tahun.
MAX_PERIODS = 8
# Sumber skor per triwulan. Skor dan jumlah responden tidak ada di dalam PDF
# publikasi (berkasnya hanya memuat tiga unsur terendah dan tindak lanjutnya),
# jadi dicatat manual di berkas data ini. Dokumennya sendiri diambil dari Drive.
REGISTRY_PATH = os.path.join(JPATH_ROOT, 'templates', 'pn_natuna_2026', 'data', 'survey-periods.json')
SURVEY_OPEN_RE = re.compile(r'<div class="survey-widget"[^>]*>')
SURVEY_CLOSE = '<!-- /survey-widget -->'

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


ENTRY_RE = re.compile(
    r'<div class="flip-entry" id="entry-([A-Za-z0-9_-]+)"[^>]*>.*?flip-entry-title">([^<]+)</div>',
    re.S,
)
YEAR_NAME_RE = re.compile(r'^(20\d{2})$')

def list_folder(folder_id):
    """List file (id, name) dari public Google Drive folder."""
    url = f'https://drive.google.com/embeddedfolderview?id={folder_id}#list'
    html = fetch(url).decode('utf-8', 'ignore')
    ids = re.findall(r'/file/d/([a-zA-Z0-9_-]+)', html)
    names = re.findall(r'flip-entry-title">([^<]+)</div>', html)
    return list(zip(ids, names))

def list_year_folders(root_id):
    """Semua subfolder bernama tahun di folder induk, terbaru lebih dulu."""
    url = f'https://drive.google.com/embeddedfolderview?id={root_id}#list'
    page = fetch(url).decode('utf-8', 'ignore')
    folders = []
    for match in ENTRY_RE.finditer(page):
        name = html.unescape(match.group(2)).strip()
        year_match = YEAR_NAME_RE.match(name)
        if year_match:
            folders.append({'id': match.group(1), 'year': int(year_match.group(1)), 'name': name})
    folders.sort(key=lambda f: f['year'], reverse=True)
    return folders

def pick_year_folder(folders, year):
    """Folder tahun yang diminta, atau None bila belum ada."""
    return next((folder for folder in folders if folder['year'] == year), None)

def resolve_section(container_id, name, groups=GROUPS):
    """ID folder seksi, dicari di akar lalu di dalam tiap grup.

    Folder induk dibagi menjadi `Beranda` dan `Transparansi`. Seksi boleh berada
    langsung di akar (struktur lama) atau di dalam salah satu grup, jadi keduanya
    dicari: memindahkan folder tidak boleh membuat cron berhenti menemukannya.
    """
    key = re.sub(r'[^a-z0-9]+', ' ', name.lower()).strip()
    page = fetch(f'https://drive.google.com/embeddedfolderview?id={container_id}#list').decode('utf-8', 'ignore')
    entries = []
    for match in ENTRY_RE.finditer(page):
        entries.append({'id': match.group(1), 'name': html.unescape(match.group(2)).strip()})
    for entry in entries:
        if re.sub(r'[^a-z0-9]+', ' ', entry['name'].lower()).strip() == key:
            return entry['id']
    for group in groups:
        group_entry = next((e for e in entries if re.sub(r'[^a-z0-9]+', ' ', e['name'].lower()).strip() == group.lower()), None)
        if group_entry is None:
            continue
        page = fetch(f'https://drive.google.com/embeddedfolderview?id={group_entry["id"]}#list').decode('utf-8', 'ignore')
        for match in ENTRY_RE.finditer(page):
            if re.sub(r'[^a-z0-9]+', ' ', html.unescape(match.group(2)).lower()).strip() == key:
                return match.group(1)
    return None


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

def collect_periods(files, survey_type, year=None):
    """Semua triwulan survey_type di folder, terbaru lebih dulu, maksimum MAX_PERIODS.

    Kartu beranda menawarkan pilihan periode seperti widget DIPA, jadi yang dibutuhkan
    bukan hanya berkas terbaru: setiap triwulan yang punya publikasi harus tetap bisa
    dibuka. Berkas ganda untuk triwulan yang sama diambil yang pertama ditemukan.

    `year` membatasi ke satu tahun anggaran. Kartu hanya menampilkan tahun berjalan,
    jadi berkas tahun lain yang tercecer di folder tahun itu tidak ikut ditawarkan.
    """
    seen = set()
    candidates = []
    for fid, name in files:
        m = re.match(rf'^{re.escape(survey_type)}\s+TW\s*([1-4])\s+(\d{{4}})\b', name, re.I)
        if not m:
            continue
        period_year, tw = int(m.group(2)), int(m.group(1))
        if year is not None and period_year != year:
            continue
        if (period_year, tw) in seen:
            continue
        seen.add((period_year, tw))
        candidates.append({'year': period_year, 'tw': tw, 'id': fid, 'name': name})
    candidates.sort(key=lambda x: (x['year'], x['tw']), reverse=True)
    return candidates[:MAX_PERIODS]

def period_label(period):
    return f'TW{period["tw"]} {period["year"]}'

def document_path(survey_type, period):
    """Nama berkas dokumen publikasi; juga dibaca `data-maklumat-zoom` oleh templat."""
    return f'/images/surveys/{survey_type}_TW{period["tw"]}_{period["year"]}.png'

def load_registry():
    """Baca skor per triwulan dari berkas data, terbaru lebih dulu."""
    with open(REGISTRY_PATH, encoding='utf-8') as handle:
        data = json.load(handle)
    periods = []
    for entry in data.get('periods', []):
        try:
            year, tw = int(entry['year']), int(entry['tw'])
        except (KeyError, TypeError, ValueError):
            continue
        if not 1 <= tw <= 4:
            continue
        scores = {}
        for survey_type in SURVEY_TYPES:
            values = entry.get(survey_type.lower())
            if isinstance(values, dict) and values.get('score') is not None:
                scores[survey_type] = values
        if scores:
            periods.append({
                'year': year,
                'tw': tw,
                'months': str(entry.get('months') or ''),
                'scores': scores,
            })
    periods.sort(key=lambda p: (p['year'], p['tw']), reverse=True)
    return periods[:MAX_PERIODS]

def resolve_tiles(periods, year=None):
    """Pasangkan tiap skor dengan dokumennya; periode tanpa dokumen tidak ditawarkan.

    Triwulan yang skornya sudah dicatat tetapi dokumennya belum terunduh dilewati,
    bukan ditampilkan setengah jadi: kartunya harus selalu bisa diklik ke publikasi.

    `year` membatasi ke satu tahun anggaran, sejalan dengan folder Drive yang dipakai:
    kartu hanya menampilkan tahun berjalan.
    """
    usable = []
    for period in periods:
        if year is not None and period['year'] != year:
            continue
        tiles = {}
        for survey_type in SURVEY_TYPES:
            values = period['scores'].get(survey_type)
            if not values:
                continue
            path = document_path(survey_type, period)
            if not os.path.isfile(JPATH_ROOT + path):
                continue
            tiles[survey_type] = dict(values, image=path)
        if tiles:
            usable.append(dict(period, tiles=tiles))
    return usable

def format_decimal(value):
    """Angka desimal gaya Indonesia: koma, bukan titik."""
    return f'{value:.2f}'.replace('.', ',')

def build_tile(survey_type, values, period):
    """Tile skor satu jenis survei.

    Hierarki yang dituju: angka indeks paling besar, lalu persentase di kepala tile
    (berhadapan dengan label jenis), lalu jumlah responden sebagai baris fakta di
    bawah bar. Sebelumnya persentase dan responden digabung dalam satu kalimat kecil
    abu-abu yang pecah dua baris - bagian paling penting justru terlihat seperti
    catatan kaki.
    """
    title = f'Publikasi {SURVEY_LABELS[survey_type]} — {period_label(period)}'
    pct = format_decimal(values["pct"])
    respondents = int(values["respondents"])
    return (
        f'<button type="button" class="survey-score-tile"'
        f' data-maklumat-zoom="{html.escape(values["image"], quote=True)}"'
        f' data-maklumat-label="{html.escape(title, quote=True)}">'
        f'<span class="survey-score-label">{survey_type}</span>'
        f'<span class="survey-score-value">{format_decimal(values["score"])} <em>/ 4,00</em></span>'
        f'<span class="survey-score-bar"><i style="--score-pct:{values["pct"]:g}%"></i></span>'
        f'<span class="survey-score-facts">'
        f'<span class="survey-score-fact"><em>Capaian</em><strong>{pct}%</strong></span>'
        f'<span class="survey-score-fact"><em>Responden</em><strong>{respondents}</strong></span>'
        f'</span>'
        f'</button>'
    )

def build_note(period):
    """Catatan di bawah tile: rentang bulan dan cara membuka dokumen.

    Tidak menyebut pembanding maupun mengulang label triwulan: pemilih di atas tile
    sudah menyebut "TW3 2026", dan menuliskannya lagi di sini membuat kalimatnya
    panjang sampai dua baris di kartu sidebar. Pembanding antartriwulan juga tidak
    lagi ditulis karena badge selisih poin sudah dihapus.
    """
    span = period['months'] or period_label(period)
    return f'{span} {period["year"]} · klik kartu untuk publikasi.'

def build_html(periods):
    """Kartu skor dengan pemilih triwulan.

    Panel teraktif dirender `is-active` dari server, jadi angkanya sudah benar sebelum
    JS jalan - tanpa JS pemilihnya tidak berpindah, tapi tidak ada panel kosong. Pola
    yang sama dengan widget DIPA dan tab Kabar Instansi.
    """
    if not periods:
        return ''
    tabs, panels = [], []
    for index, period in enumerate(periods):
        active = index == 0
        slug = f'{period["year"]}-TW{period["tw"]}'
        tiles = ''.join(
            build_tile(t, period['tiles'][t], period)
            for t in SURVEY_TYPES if t in period['tiles']
        )
        tabs.append(
            f'<button type="button" role="tab" id="survey-tab-{slug}" data-survey-tab="{slug}"'
            f' aria-controls="survey-panel-{slug}" aria-selected="{"true" if active else "false"}"'
            f' tabindex="{0 if active else -1}"'
            f' class="survey-tab{" is-active" if active else ""}">{period_label(period)}</button>'
        )
        panels.append(
            f'<div class="survey-panel{" is-active" if active else ""}" id="survey-panel-{slug}"'
            f' role="tabpanel" aria-labelledby="survey-tab-{slug}"{"" if active else " hidden"}>'
            f'<div class="survey-scores">{tiles}</div>'
            f'<p class="survey-scores-note">{html.escape(build_note(period))}</p>'
            f'</div>'
        )
    picker = ''
    if len(periods) > 1:
        picker = (
            '<div class="survey-tabs" role="tablist" aria-label="Pilih periode indeks survei">'
            + ''.join(tabs) + '</div>'
        )
    return (
        '<div class="survey-widget" data-survey-board>'
        + picker
        + '<div class="survey-panels">' + ''.join(panels) + '</div>'
        + '</div>' + SURVEY_CLOSE
    )


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

def _ink_bounds(page, threshold=240):
    """Bounding box seluruh tinta pada halaman (None bila halamannya kosong)."""
    ink = page.convert('L').point(lambda value: 255 if value < threshold else 0)
    return ink.getbbox()

def build_preview(png_path):
    """Pratinjau dokumen publikasi untuk tile skor di beranda.

    Halaman ditampilkan utuh apa adanya — hanya margin putih berlebih di kiri/kanan/
    atas/bawah yang dipangkas — supaya tile terlihat seperti dokumen aslinya. Tanda
    tangan dan cap ikut terlihat walaupun kecil; memperbesarnya terpisah membuat
    pratinjau tidak lagi menyerupai berkasnya.

    Template memakai berkas `-preview.webp` bila ada dan jatuh ke PNG asli bila tidak,
    jadi langkah ini penyempurnaan, bukan syarat render.
    """
    preview_path = os.path.splitext(png_path)[0] + '-preview.webp'
    with Image.open(png_path) as source:
        source = source.convert('RGB')
        box = _ink_bounds(source)
        if box is not None:
            # Sisa putih di tepi dokumen (pemindai) ikut dibuang, tetapi sisakan sedikit
            # bingkai supaya halaman tidak terasa terpotong menempel tepi kartu.
            pad = round(min(source.size) * 0.02)
            box = (max(0, box[0] - pad), max(0, box[1] - pad),
                   min(source.width, box[2] + pad), min(source.height, box[3] + pad))
            page = source.crop(box)
        else:
            page = source
        height = round(page.height * PREVIEW_WIDTH / page.width)
        page.resize((PREVIEW_WIDTH, height), Image.LANCZOS).save(preview_path, 'WEBP', quality=84, method=6)
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


def replace_survey_block(content, widget_html):
    """Ganti blok kartu skor saja; heading dan widget DIPA tidak disentuh.

    Dua bentuk dikenali: markup baru yang dibatasi penanda `survey-widget`, dan blok
    lama `.survey-scores` + catatan yang belum berpembungkus. Cron jalan tiap jam, jadi
    fungsi ini wajib idempoten - penggantian yang meleset akan menumpuk salinan kartu.
    """
    if widget_html == '':
        return None, 'markup kartu kosong'
    match = SURVEY_OPEN_RE.search(content)
    if match:
        end = content.find(SURVEY_CLOSE, match.start())
        if end == -1:
            return None, 'penanda penutup survey-widget tidak ditemukan'
        return content[:match.start()] + widget_html + content[end + len(SURVEY_CLOSE):], ''

    start = content.find('<div class="survey-scores"')
    if start == -1:
        return None, 'blok survey-scores tidak ditemukan'
    note = content.find('<p class="survey-scores-note"', start)
    if note != -1:
        end = content.find('</p>', note)
        if end == -1:
            return None, 'penutup catatan survey-scores-note tidak ditemukan'
        end += len('</p>')
    else:
        end = content.find('</div>', start)
        if end == -1:
            return None, 'penutup survey-scores tidak ditemukan'
        end += len('</div>')
    return content[:start] + widget_html + content[end:], ''

def update_module_db(widget_html):
    """Tulis kartu skor baru ke modul; bagian lain konten dibiarkan apa adanya."""
    result = run_mysql(f'SELECT content FROM pnn_modules WHERE id = {MODULE_ID};')
    if result.returncode != 0:
        return False, result.stderr
    current = result.stdout.rstrip('\n').replace('\\n', '\n')
    updated, error = replace_survey_block(current, widget_html)
    if updated is None:
        return False, error
    if len(SURVEY_OPEN_RE.findall(updated)) != 1:
        return False, 'hasil penggantian tidak menyisakan tepat satu blok survey-widget'
    escaped = updated.replace('\\', '\\\\').replace("'", "\\'")
    result = run_mysql(f"UPDATE pnn_modules SET content = '{escaped}' WHERE id = {MODULE_ID};")
    return result.returncode == 0, result.stderr

def ensure_documents(periods, files):
    """Unduh dokumen yang belum ada di disk untuk periode yang skornya sudah dicatat.

    Hanya periode di registry yang diunduh: triwulan yang skornya belum dicatat tidak
    akan tampil di kartu, jadi mengunduhnya cuma memenuhi disk dengan berkas tak terpakai.
    """
    by_type = {stype: {fid: name for fid, name in files} for stype in SURVEY_TYPES}
    listing = {stype: collect_periods(files, stype) for stype in SURVEY_TYPES}
    downloaded = 0
    for period in periods:
        for stype in SURVEY_TYPES:
            if stype not in period['scores']:
                continue
            png_path = JPATH_ROOT + document_path(stype, period)
            if os.path.exists(png_path):
                continue
            source = next((entry for entry in listing[stype]
                           if entry['year'] == period['year'] and entry['tw'] == period['tw']), None)
            if source is None:
                print(f'      {stype} {period_label(period)}: skor tercatat tetapi berkas tidak ada di Drive')
                continue
            pdf_path = os.path.splitext(png_path)[0] + '.pdf'
            print(f'      {stype} {period_label(period)}: download...')
            size = gdrive_download(source['id'], pdf_path)
            print(f'        PDF: {size} bytes')
            convert_pdf_to_png(pdf_path, png_path, IMG_WIDTH)
            print(f'        PNG: {os.path.getsize(png_path) // 1024}KB -> {os.path.basename(png_path)}')
            downloaded += 1
            if not os.path.exists(os.path.splitext(png_path)[0] + '-preview.webp'):
                preview = build_preview(png_path)
                print(f'        pratinjau: {os.path.getsize(preview) // 1024}KB -> {os.path.basename(preview)}')
    return downloaded

def report_unscored(listing, registry):
    """Beri tahu operator soal publikasi baru yang skornya belum dicatat."""
    known = {(p['year'], p['tw']) for p in registry}
    missing = []
    for stype, periods in listing.items():
        for entry in periods:
            key = (entry['year'], entry['tw'])
            if key not in known:
                missing.append(f'{stype} {period_label(entry)}')
    if missing:
        print('      [INFO] Publikasi berikut belum punya skor di survey-periods.json,')
        print('             jadi belum ditawarkan di kartu: ' + ', '.join(sorted(set(missing))))

def main():
    os.makedirs(OUT_DIR, exist_ok=True)

    print('[1/4] Baca skor per triwulan...')
    registry = load_registry()
    if not registry:
        print('[ERROR] survey-periods.json tidak memuat periode yang bisa dipakai.')
        sys.exit(1)
    for period in registry:
        print(f'      {period_label(period)}: ' + ', '.join(sorted(period['scores'])))

    year = date.today().year

    print(f'\n[2/4] Cari subfolder tahun {year} di Google Drive...')
    folders = list_year_folders(ROOT_FOLDER_ID)
    if not folders:
        print('[ERROR] Folder induk tidak memuat subfolder tahun.')
        sys.exit(1)
    print('      Tersedia: ' + ', '.join(str(f['year']) for f in folders))
    folder = pick_year_folder(folders, year)
    if folder is None:
        # Jangan kosongkan kartu: awal tahun anggaran baru, folder tahun berjalan
        # sering belum dibuat. Konten lama dibiarkan sampai publikasi pertama masuk.
        print(f'[WARN] Subfolder {year} belum ada; kartu tidak diubah.')
        sys.exit(1)
    print(f'      Memakai {folder["name"]} ({folder["id"]})')

    files = list_folder(folder['id'])
    print(f'      Ditemukan {len(files)} file:')
    for fid, name in files:
        print(f'        - {name}')
    listing = {stype: collect_periods(files, stype, year) for stype in SURVEY_TYPES}
    report_unscored(listing, registry)

    print('\n[3/4] Download & convert PDF -> PNG...')
    downloaded = ensure_documents(registry, files)
    if not downloaded:
        print('      Semua dokumen periode sudah ada.')

    print('\n[4/4] Update module Joomla (DB)...')
    periods = resolve_tiles(registry, year)
    if not periods:
        print(f'      [WARN] Belum ada periode {year} yang lengkap (skor + dokumen); modul tidak diubah.')
        sys.exit(1)
    ok, error = update_module_db(build_html(periods))
    if not ok:
        print(f'      [WARN] DB update gagal: {error}')
        sys.exit(1)
    print(f'      Module updated. Pemilih menawarkan {len(periods)} periode, '
          f'teraktif {period_label(periods[0])}.')

    print('\nSELESAI.')

if __name__ == '__main__':
    try:
        main()
    except Exception:
        print('[ERROR] Refresh survei gagal.', file=sys.stderr)
        sys.exit(1)
