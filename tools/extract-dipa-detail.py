#!/usr/bin/env python3
"""Ekstrak rincian realisasi DIPA per akun dari laporan PDF.

Poster Instagram butuh "apa yang paling banyak terserap" beserta angka sebelumnya,
dan modul Joomla hanya menyimpan dua baris (DIPA 01 dan DIPA 03). Angka per akun
belanja ada di PDF laporan; skrip ini membacanya dan menulis JSON supaya poster PHP
tidak perlu tahu apa pun soal PDF.

Tata letak PDF-nya landscape dan teksnya diputar 90°, jadi:
  - satu baris  = kata-kata dengan koordinat x yang sama
  - satu kolom  = koordinat y: sisa (19), % (~100), s.d. periode (130),
                  periode lalu (205), periode ini (280), lock (354), pagu (429)
  - label       = y 500..760 (rata kanan), kode akun = y >= 762

Kolom angka pada satu baris bisa bergeser beberapa poin dan label panjang turun ke
baris berikutnya, karena itu setiap kata ditempelkan ke baris angka terdekat (toleransi
9 pt) alih-alih dikelompokkan per koordinat persis.

Pemakaian:
    python tools/extract-dipa-detail.py --pdf laporan.pdf --out detail.json
    python tools/extract-dipa-detail.py --url "https://drive.google.com/file/d/ID/view" --out detail.json
"""
from __future__ import annotations

import argparse
import json
import re
import sys
import urllib.request
from collections import defaultdict
from pathlib import Path

NUMBER = re.compile(r'^[\d.,]+%?$')
ACCOUNT = re.compile(r'^\d{6}$')
# Kolom di PDF: sisa, %, s.d. periode, periode ini, periode lalu, lock, pagu.
# `periode ini` = realisasi bulan berjalan saja; `periode lalu` = kumulatif sampai
# bulan sebelumnya. Keduanya berjumlah tepat sama dengan `s.d. periode`, dan itu
# yang dipakai untuk memeriksa apakah pemetaan kolom masih benar.
COLUMNS = {"sisa": 19, "pct": 100, "real": 130, "bulan_ini": 205, "sebelumnya": 280,
           "lock": 354, "pagu": 429}
LABEL_MIN, LABEL_MAX, CODE_MIN = 500, 760, 762
ROW_TOLERANCE = 9


def download(url: str, destination: Path) -> Path:
    """Unduh PDF dari tautan Drive (`/file/d/<id>/view`) atau URL langsung."""
    match = re.search(r'/file/d/([A-Za-z0-9_-]+)', url)
    target = f'https://drive.google.com/uc?export=download&id={match.group(1)}' if match else url
    request = urllib.request.Request(target, headers={'User-Agent': 'PN-Natuna-Poster/1.0'})
    with urllib.request.urlopen(request, timeout=120) as response:
        data = response.read()
    if not data.startswith(b'%PDF-'):
        raise SystemExit('Unduhan bukan PDF; periksa izin berkas di Drive.')
    destination.parent.mkdir(parents=True, exist_ok=True)
    destination.write_bytes(data)
    return destination


def parse_page(page) -> list[dict]:
    words = page.get_text('words')
    anchors = sorted({round(word[0]) for word in words
                      if 120 <= word[1] <= 480 and NUMBER.match(word[4])})
    rows: list[int] = []
    for value in anchors:
        if rows and value - rows[-1] < 6:
            continue
        rows.append(value)
    if not rows:
        return []

    cells = {row: {'label': [], 'code': [], 'values': defaultdict(list)} for row in rows}
    for x0, y0, _x1, _y1, text, *_ in words:
        if x0 < 150:
            continue
        nearest = min(rows, key=lambda row: (abs(row - x0), row))
        if abs(nearest - x0) > ROW_TOLERANCE:
            continue
        if y0 >= CODE_MIN:
            cells[nearest]['code'].append((y0, text))
        elif y0 >= LABEL_MIN:
            cells[nearest]['label'].append((round(x0), y0, text))
        elif NUMBER.match(text):
            column = min(COLUMNS, key=lambda key: abs(COLUMNS[key] - y0))
            cells[nearest]['values'][column].append((y0, text))

    parsed = []
    for row in rows:
        lines: dict[int, list] = defaultdict(list)
        for x0, y0, text in cells[row]['label']:
            lines[x0].append((y0, text))
        label = ' '.join(
            ' '.join(text for _, text in sorted(lines[x], key=lambda item: -item[0]))
            for x in sorted(lines)
        ).strip()
        values = {}
        for column, items in cells[row]['values'].items():
            raw = ''.join(text for _, text in sorted(items))
            values[column] = (raw.replace('%', '') + '%') if column == 'pct' else raw
        code = ''.join(text for _, text in sorted(cells[row]['code'], key=lambda item: -item[0])).strip()
        parsed.append({'code': code, 'label': label, 'values': values})
    return parsed


def to_number(value: str) -> float:
    try:
        return float(str(value).replace(',', '').replace('%', ''))
    except ValueError:
        return 0.0


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--pdf', type=Path)
    parser.add_argument('--url')
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--top', type=int, default=10)
    args = parser.parse_args()

    if args.pdf is None and args.url is None:
        parser.error('berikan --pdf atau --url')
    source = args.pdf if args.pdf else download(args.url, Path(args.out).with_suffix('.pdf'))

    try:
        import pymupdf
    except ImportError:
        raise SystemExit('pymupdf tidak tersedia; jalankan: pip install pymupdf')

    document = pymupdf.open(source)
    rows = []
    for page in document:
        rows += parse_page(page)
    document.close()

    accounts = [row for row in rows if ACCOUNT.fullmatch(row['code'])]
    if not accounts:
        raise SystemExit('Tidak ada baris akun yang terbaca; periksa format PDF.')

    # Satu akun bisa muncul di beberapa komponen; gabungkan supaya "top 10" tidak
    # memuat dua baris dengan nama sama.
    merged: dict[str, dict] = {}
    for row in accounts:
        key = f"{row['code']}|{row['label']}"
        entry = merged.setdefault(key, {
            'code': row['code'], 'label': row['label'],
            'pagu': 0.0, 'realisasi': 0.0, 'sebelumnya': 0.0, 'periode_ini': 0.0,
        })
        entry['pagu'] += to_number(row['values'].get('pagu', '0'))
        entry['realisasi'] += to_number(row['values'].get('real', '0'))
        entry['sebelumnya'] += to_number(row['values'].get('sebelumnya', '0'))
        entry['periode_ini'] += to_number(row['values'].get('bulan_ini', '0'))

    items = list(merged.values())
    for item in items:
        item['persen'] = round(item['realisasi'] / item['pagu'] * 100, 2) if item['pagu'] else 0.0
        item['kenaikan'] = round(item['realisasi'] - item['sebelumnya'], 2)
    items.sort(key=lambda item: -item['realisasi'])

    # Pemeriksaan diri: kalau kolom bergeser (format PDF berubah), angka "sebelumnya"
    # akan kacau tanpa error apa pun. Selisih >2% dianggap pemetaan kolom rusak.
    mismatched = [item for item in items
                  if abs((item['sebelumnya'] + item['periode_ini']) - item['realisasi'])
                  > max(2.0, item['realisasi'] * 0.02)]
    if mismatched:
        print(f"peringatan: {len(mismatched)} baris tidak konsisten "
              f"(sebelumnya + bulan ini != realisasi), mis. {mismatched[0]['label']}",
              file=sys.stderr)

    total_pagu = sum(item['pagu'] for item in items)
    total_real = sum(item['realisasi'] for item in items)
    payload = {
        'total_pagu': total_pagu,
        'total_realisasi': total_real,
        'total_sebelumnya': sum(item['sebelumnya'] for item in items),
        'akun': len(items),
        'items': items[:args.top],
    }
    args.out.parent.mkdir(parents=True, exist_ok=True)
    args.out.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f"{args.out} — {len(items)} akun, top {len(payload['items'])}, "
          f"pagu {total_pagu:,.0f}, realisasi {total_real:,.0f}")
    return 0


if __name__ == '__main__':
    sys.exit(main())
