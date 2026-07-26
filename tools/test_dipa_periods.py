"""Kontrak pemilih periode dan delta bulanan kartu DIPA.

Dua hal yang dijaga:

1. Kartu menawarkan setiap bulan yang benar-benar ada di folder Drive, bukan
   hanya yang terbaru. Satu bulan bisa punya beberapa berkas; yang dipakai
   adalah laporan gabungan `01 dan 03` karena hanya itu memuat kedua unit.

2. Delta dihitung terhadap periode yang TERSEDIA, bukan bulan kalender. Kalau
   Mei tidak ada di folder, pembanding Juni adalah April - dan itu dinyatakan
   lewat `delta_vs` supaya kartunya tidak berbohong soal apa yang dibandingkan.

Satuannya poin persentase, bukan persen dari persen: serapan kumulatif naik
dari 38,20% ke 54,96% adalah +16,76 poin.
"""
from __future__ import annotations

import importlib.util
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / 'tools' / 'refresh-dipa.py'

failures: list[str] = []


def expect(condition: bool, message: str) -> None:
    if not condition:
        failures.append(message)


spec = importlib.util.spec_from_file_location('refresh_dipa_periods_under_test', SCRIPT)
rd = importlib.util.module_from_spec(spec)
spec.loader.exec_module(rd)


def unit(pct: float) -> dict:
    return {'pct': pct, 'pagu': 14_335_987_000, 'realisasi': int(14_335_987_000 * pct / 100)}


# --- collect_periods: satu berkas per bulan, gabungan menang ----------------
files = [
    ('ID_JUN_BOTH', 'Laporan Realisasi Anggaran DIPA 01 dan 03 Juni 2026.pdf'),
    ('ID_JUN_ONLY01', 'Laporan Realisasi Anggaran DIPA 01 Juni 2026.pdf'),
    ('ID_APR_BOTH', 'Laporan Realisasi Anggaran DIPA 01 dan 03 April 2026.pdf'),
    ('ID_FEB_BOTH', 'Laporan Realisasi Anggaran DIPA 01 dan 03 Februari 2026.pdf'),
    ('ID_NOTES', 'Catatan rapat anggaran.docx'),
    ('ID_NOPERIOD', 'Laporan Realisasi Anggaran DIPA tanpa periode.pdf'),
]
periods = rd.collect_periods(files)
expect(len(periods) == 3, f'Harus mengumpulkan tiga bulan yang valid, dapat {len(periods)}.')
expect(
    [(p['year'], p['month']) for p in periods] == [(2026, 6), (2026, 4), (2026, 2)],
    f'Periode harus terurut terbaru lebih dulu, dapat {[(p["year"], p["month"]) for p in periods]}.',
)
june = next((p for p in periods if p['month'] == 6), None)
expect(june is not None and june['id'] == 'ID_JUN_BOTH',
       'Untuk bulan yang punya dua berkas, laporan gabungan 01 dan 03 harus dipilih.')
expect(all(p['id'] != 'ID_NOTES' for p in periods), 'Berkas non-PDF tidak boleh masuk.')
expect(all(p['id'] != 'ID_NOPERIOD' for p in periods), 'PDF tanpa bulan/tahun tidak bisa jadi periode.')
expect(len(rd.collect_periods(files * 8)) <= rd.MAX_PERIODS,
       f'Jumlah periode harus dibatasi MAX_PERIODS ({rd.MAX_PERIODS}).')

# --- attach_deltas: pembanding adalah periode tersedia, Mei bolong ----------
data = [
    {'id': 'A', 'name': 'Juni', 'year': 2026, 'month': 6, 'data': {'01': unit(54.96), '03': unit(42.46)}},
    {'id': 'B', 'name': 'April', 'year': 2026, 'month': 4, 'data': {'01': unit(38.20), '03': unit(44.10)}},
    {'id': 'C', 'name': 'Februari', 'year': 2026, 'month': 2, 'data': {'01': unit(12.05), '03': unit(5.00)}},
]
rd.attach_deltas(data)
by_month = {p['month']: p['data'] for p in data}

expect(by_month[2]['01']['delta'] is None, 'Periode paling awal tidak punya pembanding.')
expect(by_month[2]['01']['delta_vs'] == '', 'Periode paling awal tidak boleh menyebut pembanding.')
expect(abs(by_month[4]['01']['delta'] - 26.15) < 0.005,
       f"April harus +26,15 poin vs Februari, dapat {by_month[4]['01']['delta']}.")
expect(by_month[4]['01']['delta_vs'] == 'Februari 2026',
       f"Pembanding April harus Februari 2026, dapat {by_month[4]['01']['delta_vs']}.")
expect(abs(by_month[6]['01']['delta'] - 16.76) < 0.005,
       f"Juni harus +16,76 poin vs April, dapat {by_month[6]['01']['delta']}.")
expect(by_month[6]['01']['delta_vs'] == 'April 2026',
       'Mei tidak ada di folder, jadi pembanding Juni wajib April - bukan Mei.')
expect(by_month[6]['03']['delta'] < 0,
       'Penurunan harus tetap dihitung, bukan disembunyikan; itu tanda data perlu diperiksa.')

# --- format_delta: arah dan glyph ------------------------------------------
expect(rd.format_delta(None)[0] is None, 'Delta kosong tidak boleh menghasilkan badge.')
expect(rd.format_delta(8.86)[0] == 'is-up', 'Kenaikan harus is-up.')
expect(rd.format_delta(-1.64)[0] == 'is-down', 'Penurunan harus is-down.')
expect(rd.format_delta(0.0)[0] == 'is-flat', 'Tanpa perubahan harus is-flat.')
expect(rd.format_delta(8.86)[2] == '+8,86', f'Angka delta harus koma desimal, dapat {rd.format_delta(8.86)[2]}.')

# --- build_html: tab, panel, dan panel aktif dari server -------------------
markup = rd.build_html(data)
tabs = re.findall(r'data-dipa-tab="([^"]*)"', markup)
expect(tabs == ['2026-06', '2026-04', '2026-02'], f'Tab harus satu per periode, terbaru dulu. Dapat {tabs}.')
expect(markup.count('role="tabpanel"') == 3, 'Harus ada satu panel per periode.')
expect(markup.count('aria-selected="true"') == 1, 'Tepat satu tab boleh aktif.')
expect(markup.count('class="dipa-panel is-active"') == 1,
       'Panel aktif wajib ditandai dari server supaya kartunya benar sebelum JS jalan.')
expect(markup.count(' hidden>') == 2, 'Panel non-aktif wajib hidden, bukan hanya disembunyikan CSS.')
expect('role="tablist"' in markup, 'Pemilih periode wajib tablist agar keyboard bekerja.')
expect(markup.count('dipa-delta is-none') == 2,
       'Kedua unit pada periode paling awal harus menyatakan "periode awal", bukan badge kosong.')

single = rd.build_html([data[0]])
expect('role="tablist"' not in single, 'Satu periode saja tidak perlu pemilih.')
expect(single.count('role="tabpanel"') == 1, 'Satu periode harus tetap merender panelnya.')
expect(rd.build_html([]) == '', 'Tanpa periode yang terparse, widget harus kosong dan DB tidak diubah.')
expect(rd.build_html([{'id': 'X', 'year': 2026, 'month': 6, 'data': {}}]) == '',
       'Periode tanpa data DIPA tidak boleh menghasilkan kartu kosong.')

# --- update_module_db: mengganti, bukan menumpuk -------------------------
# Modul 816 memuat skor SKM/IPAK lalu widget DIPA. Refresh jalan tiap hari, jadi
# menjalankannya dua kali wajib menghasilkan konten yang sama persis. Pernah
# tidak: tag pembuka widget diberi atribut `data-dipa-board`, pencocokan literal
# di update_module_db meleset, dan tiap refresh menambah satu salinan widget.
SKM = '<h2>Kinerja</h2><div class="survey-scores">skor SKM dan IPAK</div>'


class FakeResult:
    def __init__(self, stdout='', returncode=0, stderr=''):
        self.stdout, self.returncode, self.stderr = stdout, returncode, stderr


def fake_db(initial):
    """Ganti run_mysql dengan penyimpan konten di memori."""
    state = {'content': initial, 'writes': 0}

    def run(sql):
        if sql.lstrip().upper().startswith('SELECT'):
            return FakeResult(stdout=state['content'] + '\n')
        written = re.match(r"UPDATE .*? SET content = '(.*)' WHERE", sql, re.S).group(1)
        state['content'] = written.replace("\\'", "'")
        state['writes'] += 1
        return FakeResult()

    return state, run


asli = rd.run_mysql
try:
    # Dua kali refresh berturut-turut: satu blok, bukan dua.
    state, rd.run_mysql = fake_db(SKM + markup)
    for _ in range(2):
        ok, err = rd.update_module_db(markup)
        expect(ok, f'Refresh berulang harus berhasil, dapat {err!r}.')
    blocks = len(rd.WIDGET_OPEN_RE.findall(state['content']))
    expect(blocks == 1, f'Refresh dua kali harus menyisakan satu blok widget, dapat {blocks}.')
    expect(state['content'].startswith(SKM), 'Skor SKM/IPAK di atas widget tidak boleh ikut tertimpa.')

    # Tag pembuka versi lama (tanpa atribut) harus tetap dikenali dan diganti.
    state, rd.run_mysql = fake_db(SKM + '<div class="dipa-widget">kartu versi lama</div>')
    ok, _ = rd.update_module_db(markup)
    expect(ok and len(rd.WIDGET_OPEN_RE.findall(state['content'])) == 1,
           'Blok widget dengan tag pembuka lama wajib diganti, bukan ditambahi.')
    expect('kartu versi lama' not in state['content'], 'Sisa markup versi lama harus hilang.')

    # Modul yang belum punya widget: tambahkan sekali.
    state, rd.run_mysql = fake_db(SKM)
    rd.update_module_db(markup)
    expect(len(rd.WIDGET_OPEN_RE.findall(state['content'])) == 1,
           'Modul tanpa widget harus mendapat tepat satu blok.')

    # Sudah terlanjur dobel: tolak dan jangan menulis apa pun.
    state, rd.run_mysql = fake_db(SKM + markup + markup)
    ok, err = rd.update_module_db(markup)
    expect(not ok, 'Konten yang sudah memuat dua blok widget harus ditolak, bukan ditebak.')
    expect(state['writes'] == 0, 'Penolakan tidak boleh menyentuh database.')
    expect('2 blok' in err, f'Pesan galat harus menyebut jumlah blok yang ditemukan, dapat {err!r}.')
finally:
    rd.run_mysql = asli

if failures:
    sys.stderr.write('\n'.join(failures) + '\n')
    raise SystemExit(1)
print('DIPA period picker contract: ok')
