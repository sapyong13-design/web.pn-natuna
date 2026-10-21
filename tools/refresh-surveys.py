#!/usr/bin/env python3
"""Sinkronkan tiga halaman laporan survei dengan folder Google Drive.

Halaman `/transparansi/laporan-skm`, `/laporan-spak`, dan `/laporan-survei-harian`
tidak membaca Drive saat dibuka: `transparency-family.php` hanya mencetak
`introtext` artikel. Skrip ini yang memperbarui daftar kartunya.

Sumber: satu folder induk berisi tiga subfolder kategori, masing-masing berisi
subfolder tahun. Pemetaan ke artikel ada di `CATEGORIES`.

Yang diganti hanya blok `<section class="transparency-archive">…</section>` dan
blok `<div class="transparency-status is-missing">…</div>`. Judul, catatan, dan
navigasi dibiarkan apa adanya supaya suntingan manual pada prosa tidak hilang.

Urutan kartu: terbaru di atas. Triwulan diurutkan menurut nomornya, bukan abjad
nama berkas — Drive memberi nama "10. …" yang akan salah urut kalau
dibandingkan sebagai teks.

Jalankan tanpa `--write` untuk melihat rencana perubahan.
"""

from __future__ import annotations

import argparse
import html
import os
import re
import subprocess
import sys
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import date, datetime

UA = "Mozilla/5.0 (compatible; pn-natuna-survey-sync)"
VIEW = "https://drive.google.com/embeddedfolderview?id={id}#list"
FILE_URL = "https://drive.google.com/file/d/{id}/view?usp=sharing"
ROOT_FOLDER = "1oinFzi9Fuvd0aHK5ItycefsP_U-PSYdX"

# Nama subfolder Drive -> (id artikel, label jenis)
CATEGORIES = {
    "Laporan SKM": (42, "SKM"),
    "Laporan IPAK": (43, "IPAK"),
    "Laporan Survei Harian": (85, "HARIAN"),
}

BLOCK_SPLIT = '<div class="flip-entry"'
ENTRY_RE = re.compile(
    r'<div class="flip-entry"[^>]*id="entry-([^"]+)"[^>]*>.*?flip-entry-title">([^<]+)<',
    re.S,
)
ARCHIVE_RE = re.compile(r'<section class="transparency-archive"[^>]*>.*?</section>', re.S)
STATUS_RE = re.compile(r'<div class="transparency-status is-missing">.*?</div>', re.S)

ROMAN = {1: "I", 2: "II", 3: "III", 4: "IV"}
MONTHS = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
]

MYSQL_BIN = os.environ.get("MYSQL_BIN", "mysql")


def listing(folder_id: str, retries: int = 3) -> list[dict]:
    last: Exception | None = None
    for _ in range(retries):
        try:
            request = urllib.request.Request(VIEW.format(id=folder_id), headers={"User-Agent": UA})
            with urllib.request.urlopen(request, timeout=45) as response:
                page = response.read().decode("utf-8", "replace")
            out = []
            for block in page.split(BLOCK_SPLIT)[1:]:
                found = ENTRY_RE.search(BLOCK_SPLIT + block)
                if found:
                    out.append({
                        "id": found.group(1),
                        "name": html.unescape(found.group(2)),
                        "folder": 'aria-label="Folder"' in block,
                    })
            return out
        except Exception as exc:
            last = exc
    raise RuntimeError(f"Gagal membaca folder {folder_id}: {last}")


def card(file_id: str, label: str) -> str:
    """Satu kartu dokumen.

    Panah ↗ digambar CSS lewat `.transparency-document::after`, jadi tidak ada
    span panah di markup. Dulu ada `<span aria-hidden="true">&nearr;</span>`
    di sini; DOMDocument meng-escape entity itu dua kali sehingga yang tampil
    di halaman justru tulisan "&nearr;" — bukan panah.
    """
    return (
        f'<a class="transparency-document" href="{FILE_URL.format(id=file_id)}" '
        'target="_blank" rel="noopener"><span>'
        f'<strong>{html.escape(label)}</strong>'
        '<small>Google Drive &mdash; buka di tab baru</small>'
        '</span></a>'
    )


def describe(kind: str, filename: str, year: str) -> tuple[str, tuple]:
    """Kembalikan (label kartu, kunci urutan) dari nama berkas Drive."""
    if kind == "HARIAN":
        month = next((i for i, m in enumerate(MONTHS, 1) if m.lower() in filename.lower()), None)
        if month is None:
            raise ValueError(f"Nama bulan tidak dikenali: {filename}")
        return f"Laporan Survei Harian Bulan {MONTHS[month - 1]} {year}", (int(year), month)

    match = re.search(r"TRIWULAN\s+(\d+)(?:\s*(?:dan|-)\s*(\d+))?", filename, re.I)
    if not match:
        raise ValueError(f"Nomor triwulan tidak dikenali: {filename}")
    first = int(match.group(1))
    second = int(match.group(2)) if match.group(2) else None
    if second:
        label = f"Laporan {kind} Triwulan {ROMAN[first]} dan {ROMAN[second]} {year}"
    else:
        label = f"Laporan {kind} Triwulan {ROMAN[first]} {year}"
    return label, (int(year), first, second or 0)


def _span(items: list[int], labels: list[str]) -> str:
    """Rangkum nomor yang hilang: berurutan jadi "A–C", sisanya dipisah koma."""
    if not items:
        return ""
    runs: list[list[int]] = [[items[0]]]
    for value in items[1:]:
        if value == runs[-1][-1] + 1:
            runs[-1].append(value)
        else:
            runs.append([value])
    parts = []
    for run in runs:
        if len(run) == 1:
            parts.append(labels[run[0] - 1])
        elif len(run) == 2:
            parts.append(f"{labels[run[0] - 1]} dan {labels[run[1] - 1]}")
        else:
            parts.append(f"{labels[run[0] - 1]}–{labels[run[-1] - 1]}")
    return ", ".join(parts)


def missing_note(kind: str, years: dict[str, set], today: date) -> str:
    """Keterangan periode yang belum tersedia, atau string kosong bila lengkap.

    Acuannya tanggal hari ini, bukan tahun terbaru di Drive: tahun yang sudah
    lewat wajib lengkap 12 bulan (atau 4 triwulan), sedangkan tahun berjalan
    hanya sampai bulan/triwulan yang sudah selesai. Tanpa acuan ini, celah
    seperti November–Desember 2025 tidak pernah dilaporkan.
    """
    gaps: list[str] = []
    for year in sorted(years, key=int):
        have = years[year]
        if not have:
            continue
        if kind == "HARIAN":
            # Bulan berjalan belum selesai, jadi belum bisa disebut terlambat.
            expected = 12 if int(year) < today.year else today.month - 1
            absent = [m for m in range(1, expected + 1) if m not in have]
            text = _span(absent, MONTHS)
            if text:
                gaps.append(f"{text} {year}")
        else:
            # Triwulan dianggap selesai setelah bulannya berakhir.
            expected = 4 if int(year) < today.year else (today.month - 1) // 3
            absent = [q for q in range(1, expected + 1) if q not in have]
            text = _span(absent, ROMAN)
            if text:
                gaps.append(f"Triwulan {text} {year}")
    if not gaps:
        return ""
    return (
        '<div class="transparency-status is-missing"><strong>'
        + "; ".join(gaps)
        + '</strong><span>Belum tersedia pada portal</span></div>'
    )


def run_mysql(sql: str, conn: dict) -> subprocess.CompletedProcess:
    # -N membuang header kolom dan --raw mencegah escaping: tanpa keduanya,
    # keluaran SELECT ikut membawa baris "introtext" dan ikut tertulis balik
    # ke artikel saat UPDATE.
    args = [MYSQL_BIN]
    if conn.get("defaults_file"):
        # Kredensial cron ada di file mode 0600, bukan di argumen perintah.
        args.append(f"--defaults-extra-file={conn['defaults_file']}")
    else:
        args += [f"-h{conn['host']}", f"-P{conn['port']}", f"-u{conn['user']}"]
        if conn["password"]:
            args.append(f"-p{conn['password']}")
    args += ["--default-character-set=utf8mb4", "-N", "--raw", conn["database"], "-e", sql]
    return subprocess.run(args, capture_output=True, text=True)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", default=ROOT_FOLDER)
    parser.add_argument("--write", action="store_true")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default=os.environ.get("DB_NAME", "pn_prod_sync"))
    parser.add_argument(
        "--defaults-file", default=os.environ.get("MYSQL_DEFAULTS_FILE", ""),
        help="File kredensial MySQL mode 0600; dipakai cron cPanel.",
    )
    parser.add_argument("--prefix", default="pnn_")
    parser.add_argument(
        "--min-interval-days", type=float, default=0.0,
        help="Lewati jalan bila sudah berjalan kurang dari N hari lalu (0 = selalu jalan).",
    )
    parser.add_argument(
        "--state-file", default=os.path.join(os.path.expanduser("~"), ".pn-natuna-surveys-last-run"),
        help="Penanda waktu jalan terakhir untuk --min-interval-days.",
    )
    args = parser.parse_args()

    # Cron induk berjalan harian; halaman ini cukup disegarkan mingguan, jadi
    # interval dijaga di sini alih-alih menambah entri cron terpisah.
    if args.min_interval_days > 0 and os.path.exists(args.state_file):
        try:
            last = datetime.fromtimestamp(os.path.getmtime(args.state_file))
            elapsed = (datetime.now() - last).total_seconds() / 86400
        except OSError:
            elapsed = args.min_interval_days
        if elapsed < args.min_interval_days:
            print(f"Dilewati: terakhir jalan {elapsed:.1f} hari lalu "
                  f"(interval {args.min_interval_days:g} hari).")
            return 0
    conn = {"host": args.host, "port": args.port, "user": args.user,
            "password": args.password, "database": args.database,
            "defaults_file": args.defaults_file}

    print("[1/4] Folder kategori...")
    top = {e["name"]: e for e in listing(args.root) if e["folder"]}
    print(f"      {len(top)} folder: {', '.join(sorted(top))}")
    unknown = sorted(set(top) - set(CATEGORIES))
    if unknown:
        print(f"      [WARN] folder belum dipetakan: {unknown}", file=sys.stderr)

    print("[2/4] Subfolder tahun...")
    year_jobs: list[tuple[str, str, str]] = []
    for name in [n for n in CATEGORIES if n in top]:
        for year in listing(top[name]["id"]):
            if year["folder"]:
                year_jobs.append((name, year["name"], year["id"]))
    with ThreadPoolExecutor(max_workers=8) as pool:
        year_files = dict(zip(
            [j[2] for j in year_jobs],
            pool.map(lambda fid: [f for f in listing(fid) if not f["folder"]],
                     [j[2] for j in year_jobs]),
        ))
    print(f"      {len(year_jobs)} folder tahun, "
          f"{sum(len(v) for v in year_files.values())} berkas")

    print("[3/4] Susun kartu...")
    per_article: dict[int, dict] = {}
    for name, year, fid in year_jobs:
        article, kind = CATEGORIES[name]
        entry = per_article.setdefault(article, {"kind": kind, "items": [], "years": {}})
        for item in year_files.get(fid, []):
            label, key = describe(kind, item["name"], year)
            entry["items"].append((key, card(item["id"], label), label))
            covered = entry["years"].setdefault(year, set())
            # Triwulan gabungan ("3 dan 4") menutup dua nomor sekaligus.
            covered.add(key[1])
            if kind != "HARIAN" and len(key) > 2 and key[2]:
                covered.add(key[2])
    for article, entry in sorted(per_article.items()):
        entry["items"].sort(key=lambda triple: triple[0], reverse=True)
        print(f"      artikel {article}: {len(entry['items'])} kartu")

    print("[4/4] Tulis...")
    changed = 0
    for article, entry in sorted(per_article.items()):
        result = run_mysql(f"SELECT introtext FROM {args.prefix}content WHERE id = {article};", conn)
        if result.returncode != 0:
            print(f"      [ERROR] baca artikel {article}: {result.stderr.strip()[:120]}",
                  file=sys.stderr)
            continue
        current = result.stdout.rstrip("\r\n")
        if not current:
            print(f"      [WARN] artikel {article} tidak ditemukan", file=sys.stderr)
            continue
        if not ARCHIVE_RE.search(current):
            print(f"      [WARN] artikel {article} tidak punya blok arsip", file=sys.stderr)
            continue

        section = (
            '<section class="transparency-archive" aria-label="Daftar dokumen">'
            + "".join(markup for _, markup, _ in entry["items"])
            + "</section>"
        )
        updated = ARCHIVE_RE.sub(lambda _: section, current, count=1)

        note = missing_note(entry["kind"], entry["years"], date.today())
        if STATUS_RE.search(updated):
            updated = STATUS_RE.sub(lambda _: note, updated, count=1)
        elif note:
            updated = updated.replace(section, section + note, 1)

        if updated == current:
            print(f"      artikel {article}: tidak berubah")
            continue
        if not args.write:
            print(f"      artikel {article}: ADA perubahan")
            changed += 1
            continue

        escaped = updated.replace("\\", "\\\\").replace("'", "\\'")
        write = run_mysql(
            f"UPDATE {args.prefix}content SET introtext = '{escaped}', "
            f"modified = UTC_TIMESTAMP() WHERE id = {article};", conn)
        if write.returncode != 0:
            print(f"      [ERROR] tulis artikel {article}: {write.stderr.strip()[:120]}",
                  file=sys.stderr)
            continue
        print(f"      artikel {article}: ditulis")
        changed += 1

    if args.write and changed:
        try:
            open(args.state_file, "w", encoding="utf-8").write(datetime.now().isoformat())
        except OSError:
            pass

    print(f"\nSelesai. {changed} artikel {'diperbarui' if args.write else 'perlu diperbarui'}.")
    if not args.write and changed:
        print("Pakai --write untuk menyimpan.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
