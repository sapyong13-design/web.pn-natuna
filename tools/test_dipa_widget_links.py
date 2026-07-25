"""Kontrak tautan kartu DIPA.

Kartu punya dua tujuan yang sengaja dipisah:
  - angka penyerapan  -> analisis rinci per DIPA di dashboard dipantx
  - laporan mentah    -> PDF di Google Drive

Satu tautan tidak bisa punya dua tujuan, jadi wadah `.dipa-link` yang dulu
membungkus seluruh grid dibuang dan tiap `.dipa-item` jadi tautannya sendiri.

Kartu TIDAK PERNAH mengambil datanya dari dipantx - keduanya membaca PDF SP2D
bulanan yang sama, jadi tidak ada yang didapat dan dashboard tidak boleh jadi
titik gagal bagi kartu. Bila `DIPA_DASHBOARD_URL` belum diset, kartu jatuh ke
laporan PDF persis seperti perilaku sebelumnya - tanpa tautan mati.
"""
from __future__ import annotations

import importlib.util
import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / 'tools' / 'refresh-dipa.py'
DASHBOARD = 'https://dipa.pn-natuna.go.id'

SAMPLE = {
    '01': {'pct': 54.96, 'pagu': 14_335_987_000, 'realisasi': 7_878_458_360},
    '03': {'pct': 42.46, 'pagu': 178_354_000, 'realisasi': 75_725_966},
}
FILE_ID = 'FILEID123'
PDF_URL = f'https://drive.google.com/file/d/{FILE_ID}/view'

failures: list[str] = []


def expect(condition: bool, message: str) -> None:
    if not condition:
        failures.append(message)


def load(dashboard_url: str):
    """Impor ulang script dengan DIPA_DASHBOARD_URL tertentu.

    URL dibaca sekali saat modul diimpor, jadi tiap mode butuh impor baru.
    """
    os.environ['DIPA_DASHBOARD_URL'] = dashboard_url
    spec = importlib.util.spec_from_file_location('refresh_dipa_under_test', SCRIPT)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def hrefs(markup: str, css_class: str) -> list[str]:
    pattern = rf'<a class="{css_class}"[^>]*href="([^"]*)"'
    return [h.replace('&amp;', '&') for h in re.findall(pattern, markup)]


def anchor_tags(markup: str, css_class: str) -> list[str]:
    return re.findall(rf'<a class="{css_class}"[^>]*>', markup)


# --- Mode dashboard terset -------------------------------------------------
live = load(DASHBOARD)
markup = live.build_html(SAMPLE, 'Juni 2026', FILE_ID, 2026, 6)

items = hrefs(markup, 'dipa-item')
expect(len(items) == 2, f'Harus ada dua item DIPA yang bisa diklik, dapat {len(items)}.')
for unit in ('01', '03'):
    target = f'{DASHBOARD}/detail?tahun=2026&bulan=6&dipa={unit}'
    expect(target in items, f'DIPA {unit} harus menaut ke analisisnya di dipantx: {target}')

actions = hrefs(markup, 'dipa-action')
expect(actions == [PDF_URL], f'Laporan PDF harus jadi aksi terpisah, dapat {actions}.')
expect(
    all('target="_blank"' in tag and 'rel="noopener"' in tag for tag in anchor_tags(markup, 'dipa-action')),
    'Tautan PDF keluar situs, jadi wajib target="_blank" rel="noopener".',
)
expect(
    all('target=' not in tag for tag in anchor_tags(markup, 'dipa-item')),
    'Analisis DIPA bukan unduhan; jangan paksa buka tab baru.',
)
expect('dipa-link-hint' not in markup, 'Hint "klik untuk PDF" tidak berlaku saat angka menaut ke dashboard.')
expect('class="dipa-link"' not in markup, 'Wadah tautan tunggal lama tidak boleh kembali.')

# --- Mode fallback: dashboard belum ada ------------------------------------
offline = load('')
fallback = offline.build_html(SAMPLE, 'Juni 2026', FILE_ID, 2026, 6)

fallback_items = hrefs(fallback, 'dipa-item')
expect(
    fallback_items == [PDF_URL, PDF_URL],
    f'Tanpa dashboard, kedua item harus jatuh ke PDF - bukan tautan mati. Dapat {fallback_items}.',
)
expect(
    all('target="_blank"' in tag for tag in anchor_tags(fallback, 'dipa-item')),
    'Pada mode fallback item menaut ke PDF, jadi wajib buka tab baru.',
)
expect('dipa-link-hint' not in fallback or 'Klik untuk lihat laporan PDF' in fallback,
       'Mode fallback wajib menjelaskan bahwa klik membuka PDF.')
expect(DASHBOARD not in fallback, 'Mode fallback tidak boleh menyebut dashboard sama sekali.')

# --- Periode kosong tidak boleh menghasilkan tautan cacat ------------------
no_period = live.build_html(SAMPLE, 'Juni 2026', FILE_ID, None, None)
expect(
    hrefs(no_period, 'dipa-item') == [PDF_URL, PDF_URL],
    'Tanpa tahun/bulan, deep link tidak bisa dibentuk; harus jatuh ke PDF.',
)

# --- Angka tetap dari PDF, bukan dari dashboard ----------------------------
expect('54,96%' in markup and '42,46%' in markup, 'Persentase hasil parse PDF wajib tetap tampil.')
expect('terserap' in markup, 'Nilai terserap wajib tetap tampil.')

if failures:
    sys.stderr.write('\n'.join(failures) + '\n')
    raise SystemExit(1)
print('DIPA widget link contract: ok')
