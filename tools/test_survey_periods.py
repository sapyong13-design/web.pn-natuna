"""Kontrak pemilih triwulan kartu SKM/IPAK.

Dua hal yang dijaga:

1. Setiap triwulan yang punya publikasi ditawarkan sebagai pilihan, bukan hanya yang
   terbaru. Kartu beranda memakai pola tab yang sama dengan widget DIPA, jadi pembaca
   bisa membandingkan TW1, TW2, dan seterusnya tanpa meninggalkan beranda.

2. Periode yang skornya tercatat tetapi dokumennya belum ada TIDAK ditawarkan. Tile
   menyimpan jalur dokumen di `data-maklumat-zoom`; menampilkannya tanpa berkas berarti
   kartu yang diklik berakhir di gambar rusak.

Skor tidak dibaca dari PDF: publikasi hanya memuat tiga unsur terendah dan tindak
lanjutnya. Sumber skor adalah `templates/pn_natuna_2026/data/survey-periods.json`.

Run: python tools/test_survey_periods.py
"""
import importlib.util
import json
import os
import re
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / 'tools' / 'refresh-survey.py'

failures = []

def expect(condition, message):
    if not condition:
        failures.append(message)

spec = importlib.util.spec_from_file_location('refresh_survey_under_test', SCRIPT)
rs = importlib.util.module_from_spec(spec)
spec.loader.exec_module(rs)

# --- collect_periods: semua triwulan, terbaru dulu, ganda tidak berlipat ---------
files = [
    ('A', 'SKM TW 3 2026.pdf'),
    ('B', 'SKM TW2 2026.pdf'),
    ('C', 'SKM TW1 2026.pdf'),
    ('C2', 'SKM TW 1 2026.pdf'),
    ('D', 'SKM TW5 2026.pdf'),
    ('E', 'SKM TW4 2025.pdf'),
    ('F', 'LAPORAN SKM TW3 2026.pdf'),
]
periods = rs.collect_periods(files, 'SKM')
# TW4 2025 ikut terbawa: kartu menawarkan riwayat lintas tahun, bukan hanya 2026.
expect([(p['year'], p['tw']) for p in periods] == [(2026, 3), (2026, 2), (2026, 1), (2025, 4)],
       f'Triwulan harus lengkap dan terurut terbaru dulu, dapat {[(p["year"], p["tw"]) for p in periods]}.')
expect(len(periods) == 4, f'Berkas ganda dan TW tidak valid tidak boleh menambah periode, dapat {len(periods)}.')
expect(rs.collect_periods(files * 10, 'SKM') and len(rs.collect_periods(files * 10, 'SKM')) <= rs.MAX_PERIODS,
       f'Jumlah periode harus dibatasi MAX_PERIODS ({rs.MAX_PERIODS}).')
expect(rs.collect_periods(files, 'IPAK') == [], 'Periode SKM tidak boleh muncul sebagai periode IPAK.')

# --- folder tahun: auto-detect 2026, 2027, dst --------------------------------
folders = [
    {'id': 'F2028', 'year': 2028, 'name': '2028'},
    {'id': 'F2027', 'year': 2027, 'name': '2027'},
    {'id': 'F2026', 'year': 2026, 'name': '2026'},
]
expect(rs.pick_year_folder(folders, 2027)['id'] == 'F2027', 'Folder tahun berjalan harus dipilih.')
expect(rs.pick_year_folder(folders, 2029) is None,
       'Tahun tanpa subfolder harus menghasilkan None, bukan menebak folder lain.')
expect(rs.YEAR_NAME_RE.match('2026') and not rs.YEAR_NAME_RE.match('TW 2026'),
       'Hanya nama empat digit yang dianggap folder tahun.')

# Berkas tahun lain di folder tahun berjalan tidak ikut ditawarkan.
mixed = [
    ('A', 'SKM TW 3 2026.pdf'),
    ('B', 'SKM TW4 2025.pdf'),
    ('C', 'SKM TW1 2027.pdf'),
]
scoped = rs.collect_periods(mixed, 'SKM', 2026)
expect([(p['year'], p['tw']) for p in scoped] == [(2026, 3)],
       f'Periode harus dibatasi tahun berjalan, dapat {[(p["year"], p["tw"]) for p in scoped]}.')

# --- find_latest tetap memberi berkas terbaru -----------------------------------
expect(rs.find_latest(files, 'SKM')['tw'] == 3, 'find_latest harus tetap mengembalikan triwulan terbaru.')

# --- registry: skor dibaca dari berkas data -------------------------------------
registry = rs.load_registry()
expect(registry, 'Registry skor tidak boleh kosong.')
expect([(p['year'], p['tw']) for p in registry] == sorted(
    [(p['year'], p['tw']) for p in registry], reverse=True), 'Registry harus terurut terbaru dulu.')
for period in registry:
    expect(period['scores'], f'Periode {rs.period_label(period)} harus punya minimal satu skor.')
    for survey_type, values in period['scores'].items():
        expect(survey_type in rs.SURVEY_TYPES, f'Jenis survei tidak dikenal: {survey_type}.')
        expect(isinstance(values.get('score'), (int, float)) and 0 < values['score'] <= 4,
               f'Skor {survey_type} {rs.period_label(period)} harus di antara 0 dan 4.')
        expect(isinstance(values.get('respondents'), int) and values['respondents'] > 0,
               f'Jumlah responden {survey_type} {rs.period_label(period)} harus bilangan bulat positif.')

# --- resolve_tiles: periode tanpa dokumen tidak ditawarkan ----------------------
missing = rs.resolve_tiles([{'year': 2026, 'tw': 4, 'months': '', 'scores': {'SKM': {'score': 4.0, 'pct': 100, 'respondents': 1}}}])
expect(missing == [], 'Periode tanpa dokumen di disk tidak boleh ditawarkan.')

with_doc = next(p for p in registry if os.path.isfile(rs.JPATH_ROOT + rs.document_path('SKM', p)))
resolved = rs.resolve_tiles([with_doc])
expect(len(resolved) == 1, 'Periode yang dokumennya ada harus tetap ditawarkan.')

# Kartu hanya menampilkan tahun berjalan: periode tahun lain dilewati walaupun
# dokumennya ada di disk.
expect(rs.resolve_tiles([with_doc], with_doc['year'] + 1) == [],
       'Periode di luar tahun yang diminta tidak boleh ditawarkan.')
expect(len(rs.resolve_tiles([with_doc], with_doc['year'])) == 1,
       'Periode tahun yang diminta tetap ditawarkan.')
expect(resolved[0]['tiles']['SKM']['image'].endswith('.png'), 'Tile harus menyimpan jalur dokumen PNG.')

# --- kartu tidak memuat badge selisih ------------------------------------------
# Badge "+0,03 poin" pada tile pernah ada, lalu dihapus: angkanya terbaca seperti
# bagian dari indeks dan membuat kartu padat. Yang tersisa hanya skor, persentase,
# jumlah responden, dan pratinjau dokumen.
expect(not hasattr(rs, 'delta_badge'), 'Badge delta tidak boleh dikembalikan tanpa keputusan baru.')
expect(not hasattr(rs, 'attach_deltas'), 'Perhitungan delta tidak lagi dipakai kartu.')

# --- build_html: tab + panel seimbang, panel pertama aktif ----------------------
html = rs.build_html(rs.resolve_tiles(registry))
expect(html.startswith('<div class="survey-widget"'), 'Kartu harus dibungkus penanda survey-widget.')
expect(html.endswith(rs.SURVEY_CLOSE), 'Kartu harus diakhiri penanda penutup.')
tabs = re.findall(r'data-survey-tab="([^"]+)"', html)
panels = re.findall(r'id="survey-panel-([^"]+)"', html)
expect(tabs == panels, f'Tab dan panel harus berpasangan, dapat {tabs} vs {panels}.')
expect(html.count('aria-selected="true"') == 1, 'Hanya satu tab yang boleh aktif.')
expect(html.count('class="survey-score-label">SKM<') == len(tabs), 'Setiap periode butuh tile SKM.')
expect(html.count('data-maklumat-zoom=') == len(tabs) * 2, 'Setiap periode butuh dua dokumen publikasi.')

# Skor, capaian, dan responden harus tetap tiga angka terpisah. Menggabungkan capaian
# dan responden jadi satu kalimat membuat keduanya jatuh ke ukuran catatan kaki.
expect(html.count('class="survey-score-value"') == len(tabs) * 2, 'Setiap tile butuh satu angka skor.')
expect(html.count('class="survey-score-fact"') == len(tabs) * 4, 'Setiap tile butuh dua baris fakta (capaian dan responden).')
expect(html.count('<em>Capaian</em>') == len(tabs) * 2, 'Capaian harus jadi fakta tersendiri.')
expect(html.count('<em>Responden</em>') == len(tabs) * 2, 'Jumlah responden harus jadi fakta tersendiri.')
expect('responden</span>' not in html, 'Capaian dan responden tidak boleh digabung dalam satu kalimat.')
expect('survey-score-delta' not in html, 'Tile tidak boleh lagi memuat badge selisih poin.')
expect('dibanding' not in html, 'Catatan periode tidak boleh menyebut pembanding.')
# Catatan tidak boleh mengulang label triwulan: pemilih di atasnya sudah menyebutnya,
# dan pengulangan itu membuat kalimatnya memakan dua baris di kartu sidebar.
expect(html.count('Periode TW') == 0, 'Catatan periode tidak boleh mengulang label triwulan.')
expect('survey-scores-note' in html, 'Catatan periode harus ikut dirender.')

single = rs.build_html(rs.resolve_tiles(registry)[:1])
expect('survey-tabs' not in single, 'Satu periode saja tidak perlu pemilih.')

# --- replace_survey_block: idempoten, bagian lain tidak tersentuh ---------------
module = ('<h2>Kinerja &amp; Akuntabilitas</h2><div class="survey-widget" data-survey-board>'
          '<div class="survey-panels">lama</div></div>' + rs.SURVEY_CLOSE
          + '<div class="dipa-widget" data-dipa-board>DIPA</div>')
updated, error = rs.replace_survey_block(module, html)
expect(error == '', f'Penggantian blok harus berhasil, dapat {error!r}.')
expect(updated.count('data-survey-board') == 1, 'Penggantian harus menyisakan satu blok kartu.')
expect('<div class="dipa-widget" data-dipa-board>DIPA</div>' in updated, 'Widget DIPA tidak boleh tersentuh.')
again, _ = rs.replace_survey_block(updated, html)
expect(again == updated, 'Menjalankan dua kali harus menghasilkan markup yang sama persis.')

legacy = ('<h2>Kinerja</h2><div class="survey-scores"><button>lama</button></div>'
          '<p class="survey-scores-note">catatan lama</p><div class="dipa-widget">DIPA</div>')
migrated, error = rs.replace_survey_block(legacy, html)
expect(error == '', f'Markup lama harus bisa dimigrasi, dapat {error!r}.')
expect('catatan lama' not in migrated and 'DIPA' in migrated,
       'Markup lama diganti tanpa menyentuh widget DIPA.')
expect(migrated.count('data-survey-board') == 1, 'Migrasi markup lama harus menghasilkan satu blok.')

no_match, error = rs.replace_survey_block('<h2>Kinerja</h2><p>tanpa kartu</p>', html)
expect(no_match is None and error, 'Modul tanpa blok kartu harus dilaporkan, bukan ditebak.')

if failures:
    sys.stderr.write('\n'.join(failures) + '\n')
    sys.exit(1)
print('Survey periods: OK')
