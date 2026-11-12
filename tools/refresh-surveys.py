#!/usr/bin/env python3
"""Sinkronkan seluruh halaman menu Transparansi dengan folder Google Drive.

Halaman-halaman di menu Transparansi tidak membaca Drive saat dibuka:
`transparency-family.php` hanya mencetak `introtext` artikel. Skrip ini yang
memperbarui daftar kartunya.

Sumber kanonis: satu folder induk (`ROOT_FOLDER`) berisi satu subfolder per seksi,
masing-masing berisi subfolder tahun. Pemetaan seksi ke artikel ada di `SECTIONS`.

**Semua tahun ditampilkan.** Ini halaman arsip, jadi 2020 sampai 2026 harus
terbaca bersamaan - berbeda dengan kartu beranda (Kinerja/Akuntabilitas) yang
memang hanya memuat tahun berjalan.

Transisi: seksi yang subfoldernya belum ada di folder induk dibaca dari
`legacy_folders` (kalau ada). Sejak pemindahan Drive selesai, semua seksi sudah
berada di dalam `Beranda`/`Transparansi`, jadi daftar itu tidak lagi dipakai; mekanisme
tetap dipertahankan sebagai jaring pengaman bila sebuah folder perlu dikembalikan
sementara ke lokasi lama. Seksi tanpa sumber sama sekali dibiarkan apa adanya, jadi tidak
ada halaman yang kosong karena migrasi setengah jalan.

Yang diganti hanya blok `<section class="transparency-archive">…</section>` dan
blok `<div class="transparency-status is-missing">…</div>`. Judul, catatan, dan
navigasi dibiarkan apa adanya supaya suntingan manual pada prosa tidak hilang.

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

# Seksi halaman Transparansi: nama subfolder di folder induk -> konfigurasi.
#
# `kind` menentukan cara label kartu dibentuk:
#   TRIWULAN - "Laporan SKM Triwulan III 2026" (nomor triwulan dari nama berkas)
#   BULANAN  - "Laporan Survei Harian Bulan Oktober 2025"
#   FILE     - nama berkas dirapikan apa adanya (dipakai seksi yang isinya campuran)
#
# `legacy_folders` (opsional) = folder tahun di lokasi lama. Sejak struktur Drive
# dirapikan, semua seksi ada di dalam `Beranda`/`Transparansi` dan daftar ini kosong.
# Mekanisme tetap ada sebagai jaring pengaman bila sebuah folder harus dikembalikan
# sementara ke lokasi lamanya.
SECTIONS = {
    # `aliases` = folder lain yang isinya milik seksi ini. Dipakai selama Drive
    # masih menyimpan salinan lama di samping folder kanonis; berkas yang sama
    # dibuang sekali oleh dedup di build_articles().
    "Laporan SKM": {
        "article": 42, "kind": "TRIWULAN", "label": "SKM",
        "aliases": ["Laporan SKM & SPAK"],
        "only": "skm",   # folder gabungan juga memuat dokumen IPAK
    },
    "Laporan IPAK": {
        "article": 43, "kind": "TRIWULAN", "label": "IPAK",
        "aliases": ["Laporan SKM & SPAK"],
        "only": "ipak",
    },
    "Laporan Survei Harian": {
        "article": 85, "kind": "BULANAN",
        "aliases": ["Laporan Survey Harian"],
    },
    "Ringkasan LKjIP": {"article": 37, "kind": "FILE"},
    "Laporan Tahunan": {"article": 38, "kind": "FILE"},
    "SAKIP": {
        "article": 39, "kind": "FILE",
    },
    "Realisasi DIPA": {
        "article": 40, "kind": "FILE",
        "aliases": ["LRA"],
    },
    "LHKPN": {
        "article": 41, "kind": "FILE", "prefix": "LHKPN",
    },
    "Laporan Keuangan": {
        "article": 86, "kind": "FILE",
    },
    "E-Brosur": {"article": 87, "kind": "FILE"},
    "Peraturan dan Kebijakan": {"article": 88, "kind": "FILE"},
    "Laporan Pelayanan Informasi Publik": {"article": 89, "kind": "FILE"},
    "Lelang Barang dan Jasa": {"article": 90, "kind": "FILE"},
}

# Folder induk dibagi dua bagian. Seksi boleh berada langsung di folder induk
# (struktur lama) atau di dalam salah satu grup ini; keduanya dibaca.
GROUPS = ("Transparansi", "Beranda")

# Subfolder yang bukan halaman Transparansi: kartu beranda (`refresh-survey.py`)
# dan widget anggaran (`refresh-dipa.py`).
HANDLED_ELSEWHERE = {"Survei Terbaru", "Realisasi DIPA"}

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


# Singkatan yang harus tetap huruf besar saat nama berkas dirapikan.
ACRONYMS = {
    "PN", "RI", "MA", "LHKPN", "LHKASN", "WKPN", "SAKIP", "LKJIP", "IKU", "DIPA",
    "LRA", "DPA", "SKM", "IPAK", "SPT", "PTSP", "RKA", "RKT", "KPN", "PDF", "SOP",
    "PNBP", "APBD", "APBN", "RENSTRA", "RENJA", "RPD", "PKB", "TW", "SMT", "LK",
    "PK", "SK", "SE", "KEP", "UU", "PP", "PERMA", "SEMA", "KMA", "SPIP", "PNS",
    "PPPK", "CPNS", "KGB", "BKN", "ASN", "I", "II", "III", "IV",
}

def prettify(filename: str) -> str:
    """Nama berkas jadi judul kartu yang enak dibaca.

    Nama di Drive campur aduk: ada awalan nomor ("1. "), garis bawah, spasi ganda,
    dan akhiran stempel waktu ekspor ("02-18-2026-202814"). Semuanya dibuang di
    sini supaya kartu tidak menampilkan nama teknis.
    """
    name = re.sub(r'\.(pdf|docx?|xlsx?|pptx?|zip|rar)$', '', filename, flags=re.I)
    name = re.sub(r'^\s*\d+[.)]\s*', '', name)                 # "1. ", "2) "
    name = re.sub(r'\s*\d{2}-\d{2}-\d{4}-\d{6}\s*$', '', name)  # stempel ekspor
    name = name.replace('_', ' ')
    name = re.sub(r'\s+', ' ', name).strip(' -')
    # Nama yang seluruhnya huruf besar dirapikan; nama yang sudah campuran
    # dibiarkan apa adanya supaya penulisan resmi tidak berubah.
    letters = [c for c in name if c.isalpha()]
    if letters and sum(1 for c in letters if c.isupper()) / len(letters) > 0.9:
        words = []
        for word in name.split(' '):
            upper = word.upper()
            words.append(upper if upper in ACRONYMS else word.capitalize())
        name = ' '.join(words)
    # Nama bulan dinormalkan terlepas dari huruf besar/kecil berkasnya: "JULI" pada
    # nama yang sebagian sudah huruf kecil tetap harus tampil "Juli".
    months = {m.upper(): m for m in MONTHS}
    name = ' '.join(months.get(w.upper(), w) if w.upper() in months else w for w in name.split(' '))
    return name

def year_from(value: str) -> str:
    """Ambil tahun empat digit dari nama folder atau berkas apa pun bentuknya.

    Folder tahun dibuat manual di Drive dan sering tetap membawa nama lamanya
    ("SAKIP 2020") setelah dipindahkan. Tanpa ini, folder seperti itu gagal
    dikenali sebagai tahun dan seluruh seksi kehilangan urutan serta labelnya.
    """
    found = re.search(r'(20\d{2})', str(value or ''))
    return found.group(1) if found else ''

def year_from(value: str) -> str:
    """Ambil tahun empat digit dari nama folder atau berkas apa pun bentuknya.

    Folder tahun dibuat manual di Drive dan sering tetap membawa nama lamanya
    ("SAKIP 2020") setelah dipindahkan ke folder seksi. Tanpa ini, folder seperti
    itu gagal dikenali sebagai tahun dan urutan serta label kartunya rusak.
    """
    found = re.search(r'(20\d{2})', str(value or ''))
    return found.group(1) if found else ''

def month_in(filename: str) -> int:
    """Nomor bulan dari nama berkas, 0 bila tidak disebut.

    Drive memakai dua gaya: nama bulan ("LRA DIPA 01 dan 03 JULI 2026") dan angka
    ("LRA DIPA 01 dan 03_01-2025"). Keduanya dibaca supaya kartu bulan terbaru
    berada di atas, bukan urut abjad.
    """
    low = filename.lower()
    for index, name in enumerate(MONTHS, 1):
        if re.search(r'\b' + name.lower() + r'\b', low):
            return index
    # Garis bawah adalah karakter kata, jadi `\b` tidak cocok sebelum bulan pada
    # nama seperti "…_01-2025"; batasnya dicek dengan lookaround angka.
    found = re.search(r'(?<!\d)(0[1-9]|1[0-2])[-_](20\d{2})(?!\d)', low)
    return int(found.group(1)) if found else 0

def describe(kind: str, filename: str, year: str, label: str = "", prefix: str = "") -> tuple[str, tuple]:
    """Kembalikan (label kartu, kunci urutan) dari nama berkas Drive.

    `kind` menentukan cara membaca nama berkas, `label` adalah nama jenis yang
    dicetak di kartu (SKM/IPAK). Keduanya dulu satu variabel, sehingga kartu
    berbunyi "Laporan TRIWULAN Triwulan III 2026" setelah jenisnya dipisah.

    `year` boleh berupa nama folder apa adanya ("SAKIP 2020"); tahunnya diambil
    dari empat digit yang ada di dalamnya.
    """
    year = year_from(year) or (str(year) if str(year).isdigit() else '')
    year = year_from(year) or (str(year) if str(year).isdigit() else '')
    if kind == "FILE":
        text = prettify(filename)
        found = re.search(r'(20\d{2})', filename)
        sort_year = int(found.group(1)) if found else (int(year) if year.isdigit() else 0)
        # Berkas yang dinamai orang ("Sutriadi.pdf") tidak memberi tahu apa pun
        # tentang isinya: tanpa tahun dan jenis dokumen, kartu di halaman arsip
        # hanya menampilkan nama orang. Keduanya ditambahkan hanya untuk nama
        # seperti itu; berkas yang namanya sudah lengkap dibiarkan apa adanya.
        if not found:
            if year:
                text = f"{text} {year}"
            if prefix and not text.lower().startswith(prefix.lower()):
                text = f"{prefix} {text}"
        return text, (sort_year, month_in(filename), text.lower())
    # Berkas yang diletakkan langsung di folder seksi (bukan di subfolder tahun)
    # tetap butuh tahun untuk labelnya.
    if not year:
        found = re.search(r'(20\d{2})', filename)
        year = found.group(1) if found else ""
    if kind == "BULANAN":
        month = next((i for i, m in enumerate(MONTHS, 1) if m.lower() in filename.lower()), None)
        if month is None:
            raise ValueError(f"Nama bulan tidak dikenali: {filename}")
        return f"Laporan Survei Harian Bulan {MONTHS[month - 1]} {year}", (int(year), month)

    # Nomor triwulan bisa ditulis angka ("TRIWULAN 3") atau Romawi ("TRIWULAN III").
    # Berkas lama di folder gabungan memakai Romawi; tanpa dukungan ini seluruh
    # sinkronisasi berhenti pada berkas pertama yang memakainya.
    match = re.search(
        r"TRIWULAN\s+([1-4]|IV|III|II|I)(?:\s*(?:dan|-)\s*([1-4]|IV|III|II|I))?",
        filename, re.I,
    )
    if not match:
        raise ValueError(f"Nomor triwulan tidak dikenali: {filename}")
    roman = {"I": 1, "II": 2, "III": 3, "IV": 4}
    to_number = lambda value: int(value) if value.isdigit() else roman[value.upper()]
    first = to_number(match.group(1))
    second = to_number(match.group(2)) if match.group(2) else None
    kind_label = label or kind
    if second:
        text = f"Laporan {kind_label} Triwulan {ROMAN[first]} dan {ROMAN[second]} {year}"
    else:
        text = f"Laporan {kind_label} Triwulan {ROMAN[first]} {year}"
    return text, (int(year), first, second or 0)


def _span(items: list[int], labels) -> str:
    """Rangkum nomor yang hilang: berurutan jadi "A–C", sisanya dipisah koma.

    `labels` boleh list (bulan, indeks 0) atau dict (angka Romawi, kunci 1). Dulu
    keduanya diakses `labels[value - 1]`, sehingga triwulan I melempar KeyError -
    satu celah triwulan cukup untuk menggagalkan seluruh sinkronisasi - dan
    triwulan II salah menyebut dirinya "I".
    """
    if not items:
        return ""
    name = (lambda value: labels[value]) if isinstance(labels, dict) else (lambda value: labels[value - 1])
    runs: list[list[int]] = [[items[0]]]
    for value in items[1:]:
        if value == runs[-1][-1] + 1:
            runs[-1].append(value)
        else:
            runs.append([value])
    parts = []
    for run in runs:
        if len(run) == 1:
            parts.append(name(run[0]))
        elif len(run) == 2:
            parts.append(f"{name(run[0])} dan {name(run[1])}")
        else:
            parts.append(f"{name(run[0])}–{name(run[-1])}")
    return ", ".join(parts)


def missing_note(kind: str, years: dict[str, set], today: date) -> str:
    """Keterangan periode yang belum tersedia, atau string kosong bila lengkap.

    Acuannya tanggal hari ini, bukan tahun terbaru di Drive: tahun yang sudah
    lewat wajib lengkap 12 bulan (atau 4 triwulan), sedangkan tahun berjalan
    hanya sampai bulan/triwulan yang sudah selesai. Tanpa acuan ini, celah
    seperti November–Desember 2025 tidak pernah dilaporkan.
    """
    if kind == "FILE":
        # Seksi berkas campuran tidak punya jumlah baku per tahun, jadi tidak ada
        # dasar untuk menyebut periode "belum tersedia".
        return ""
    # Hanya tahun empat digit yang punya jumlah baku; kunci lain (folder tanpa
    # tahun, mis. E-Brosur) tidak bisa disebut "belum tersedia".
    gaps: list[str] = []
    for year in sorted((y for y in years if re.fullmatch(r"20\d{2}", str(y))), key=int):
        have = years[year]
        if not have:
            continue
        if kind == "BULANAN":
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
    args += ["--default-character-set=utf8mb4", "-N", "--raw", conn["database"]]
    # SQL dikirim lewat stdin, bukan `-e`. Artikel dengan ratusan kartu melewati
    # batas panjang argumen Windows (WinError 206) bila dikirim sebagai argumen.
    return subprocess.run(args, input=sql, capture_output=True, text=True)


EXISTING_CARD_RE = re.compile(
    r'<a class="transparency-document" href="([^"]+)"[^>]*>.*?<strong>(.*?)</strong>',
    re.S,
)

def existing_cards(html_text: str) -> list[tuple[str, str]]:
    """Kartu yang sudah ada di artikel, sebagai (href, label).

    Dipakai untuk mempertahankan kartu yang tautannya tidak berasal dari folder
    Drive yang sedang dibaca (mis. dokumen yang masih disimpan di folder lain).
    Sinkronisasi bersifat aditif: kartu lama tidak dihapus hanya karena berkasnya
    belum dipindahkan.
    """
    cards = []
    for href, label in EXISTING_CARD_RE.findall(html_text or ""):
        cards.append((html.unescape(href), html.unescape(re.sub(r'<[^>]+>', '', label)).strip()))
    return cards

def keep_legacy_cards(entry: dict, html_text: str) -> None:
    """Tambahkan kartu lama yang belum ada di hasil sinkronisasi."""
    known = {href for _, markup, _ in entry["items"] for href in re.findall(r'href="([^"]+)"', markup)}
    for href, label in existing_cards(html_text):
        if href in known or not label:
            continue
        year = re.search(r'(20\d{2})', label)
        key = (int(year.group(1)) if year else 0, 12, label.lower())
        entry["items"].append((key, card(href.rsplit('/', 2)[-2], label), label))
        known.add(href)

def build_articles(year_jobs: list[tuple[str, str, str]], year_files: dict) -> dict:
    """Susun kartu per artikel dari seluruh subfolder tahun.

    Semua tahun yang ada di Drive ikut ditampilkan - tidak ada penyaringan tahun
    berjalan di sini. Halaman Transparansi adalah arsip, jadi 2020 sampai 2026 harus
    tetap terbaca bersamaan. Urutan kartu terbaru lebih dulu.
    """
    per_article: dict[int, dict] = {}
    seen_files: set[str] = set()
    for name, year, fid in year_jobs:
        config = SECTIONS[name]
        article, kind = config["article"], config["kind"]
        entry = per_article.setdefault(article, {"kind": kind, "items": [], "years": {}})
        only = config.get("only", "")
        for item in year_files.get(fid, []):
            # Folder gabungan ("Laporan SKM & SPAK") memuat dua jenis dokumen.
            # Tanpa saringan ini, halaman SKM ikut menampilkan berkas IPAK.
            if only and only not in item["name"].lower() and not (
                only == "ipak" and "spak" in item["name"].lower()
            ):
                continue
            # Folder induk dan lokasi lama bisa menunjuk berkas yang sama selama
            # pemindahan; satu berkas hanya boleh muncul sekali.
            if item["id"] in seen_files:
                continue
            seen_files.add(item["id"])
            label, key = describe(kind, item["name"], year, config.get("label", ""), config.get("prefix", ""))
            entry["items"].append((key, card(item["id"], label), label))
            # Keterangan celah hanya masuk akal untuk seksi berkala, dan hanya
            # untuk tahun yang benar-benar punya berkas.
            if kind in ("TRIWULAN", "BULANAN"):
                covered = entry["years"].setdefault(year_from(year) or year, set())
                covered.add(key[1])
                # Triwulan gabungan ("3 dan 4") menutup dua nomor sekaligus.
                if kind == "TRIWULAN" and len(key) > 2 and key[2]:
                    covered.add(key[2])
    for entry in per_article.values():
        entry["items"].sort(key=lambda triple: triple[0], reverse=True)
    return per_article

def folder_key(name: str) -> str:
    """Bentuk pembanding nama folder: huruf kecil, tanpa tanda baca dan spasi ganda.

    Nama folder dibuat manual di Drive, jadi "E-Brosur", "E-brosur", dan "E brosur"
    harus dianggap sama. Tanpa ini, seksi yang foldernya sudah dipindahkan tetap
    dianggap belum ada hanya karena beda huruf besar satu karakter.
    """
    return re.sub(r'[^a-z0-9]+', ' ', name.lower()).strip()

def section_sources(root_entries: list[dict]) -> dict[str, list[tuple[str, str, str]]]:
    """Tentukan sumber tiap seksi: subfolder folder induk, dan/atau lokasi lama.

    Folder induk dan lokasi lama digabung, bukan saling menggantikan: selama
    pemindahan berjalan, folder induk baru memuat sebagian tahun (mis. DIPA hanya
    2026) sehingga memakai folder induk saja akan menghilangkan tautan 2021-2025
    dari halaman. Setelah semua folder dipindahkan, keduanya menunjuk berkas yang
    sama dan penggabungan ini tidak lagi menambah apa pun (dedup per id berkas).

    `legacy_folders` hanya dipakai bila masih didefinisikan di SECTIONS.
    """
    # Kandidat folder: isi akar folder induk, ditambah isi tiap grup (Beranda,
    # Transparansi). Seksi yang belum dipindah ke grup tetap ditemukan di akar.
    candidates = [e for e in root_entries if e["folder"]]
    by_name = {folder_key(e["name"]): e for e in candidates}
    for group in GROUPS:
        group_entry = by_name.get(folder_key(group))
        if group_entry is None:
            continue
        for child in listing(group_entry["id"]):
            if child["folder"]:
                candidates.append(child)
                by_name.setdefault(folder_key(child["name"]), child)

    sources: dict[str, list[tuple[str, str, str]]] = {}
    for name, config in SECTIONS.items():
        entries: list[tuple[str, str, str]] = []
        found = by_name.get(folder_key(name))
        if found is not None:
            entries.append(("root", "", found["id"]))
        # Salinan lama yang masih ada di samping folder kanonis.
        for alias in config.get("aliases", []):
            alias_entry = by_name.get(folder_key(alias))
            if alias_entry is not None:
                entries.append(("root", "", alias_entry["id"]))
        # Folder tahun yang diletakkan langsung di folder induk, mis. "SAKIP 2020".
        # Muncul saat folder lama dipindah apa adanya tanpa rename: tahunnya dibaca
        # dari nama folder, seksinya dari awalan nama.
        section_key = folder_key(name)
        for entry in by_name.values():
            year = year_from(entry["name"])
            if not year:
                continue
            prefix = folder_key(entry["name"].replace(year, " "))
            if prefix and section_key.startswith(prefix):
                entries.append(("year", year, entry["id"]))
        for year, fid in config.get("legacy_folders", []):
            entries.append(("year", year, fid))
        if entries:
            sources[name] = entries
    return sources

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

    print("[1/4] Folder induk...")
    root_entries = listing(args.root)
    top = {e["name"]: e for e in root_entries if e["folder"]}
    print(f"      {len(top)} folder: {', '.join(sorted(top))}")
    unknown = sorted(set(top) - set(SECTIONS) - HANDLED_ELSEWHERE - set(GROUPS))
    if unknown:
        print(f"      [WARN] folder belum dipetakan: {unknown}", file=sys.stderr)

    print("[2/4] Sumber tiap seksi...")
    sources = section_sources(root_entries)
    year_jobs: list[tuple[str, str, str]] = []
    loose: dict[str, list[dict]] = {}
    for name, entries in sources.items():
        used = []
        for origin, year, fid in entries:
            if origin == "root":
                # Subfolder seksi di folder induk: isinya subfolder tahun, ditambah
                # berkas lepas yang diletakkan langsung di situ.
                section_entries = listing(fid)
                loose.setdefault(name, []).extend(e for e in section_entries if not e["folder"])
                for entry in section_entries:
                    if entry["folder"]:
                        year_jobs.append((name, entry["name"], entry["id"]))
                used.append("folder induk")
            else:
                year_jobs.append((name, year, fid))
                used.append("lama")
        print(f"      {name}: " + " + ".join(dict.fromkeys(used)))
    def read_files(fid, depth=2):
        # Folder yang dihapus atau dijadikan privat hanya dilewati: satu tautan mati
        # tidak boleh menghentikan pembaruan seluruh halaman Transparansi.
        #
        # `depth` menangani folder tahun yang di dalamnya masih ada subfolder
        # ("1. LHKPN 2020", "2. LHKASN 2020"). Berkasnya tetap milik tahun yang sama,
        # jadi diturunkan sampai habis; tanpa ini isi tahun tersebut hilang dari arsip.
        try:
            entries = listing(fid)
        except RuntimeError as exc:
            print(f"      [WARN] {exc}", file=sys.stderr)
            return []
        files = [f for f in entries if not f["folder"]]
        if depth > 0:
            for sub in entries:
                if sub["folder"]:
                    files.extend(read_files(sub["id"], depth - 1))
        return files

    with ThreadPoolExecutor(max_workers=8) as pool:
        year_files = dict(zip(
            [j[2] for j in year_jobs],
            pool.map(read_files, [j[2] for j in year_jobs]),
        ))
    # Berkas lepas digabung sebagai satu kelompok per seksi.
    for name, files in loose.items():
        if files:
            year_files[f"loose:{name}"] = files
            year_jobs.append((name, "", f"loose:{name}"))
    print(f"      {len(year_jobs)} folder, "
          f"{sum(len(v) for v in year_files.values())} berkas")

    print("[3/4] Susun kartu...")
    per_article = build_articles(year_jobs, year_files)
    for article, entry in sorted(per_article.items()):
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

        # Kartu yang sudah ada tapi tidak berasal dari folder Drive yang dibaca
        # tetap dipertahankan. Tanpa ini, memindahkan folder ke struktur baru
        # menghapus tautan dokumen yang berkasnya belum ikut dipindah.
        keep_legacy_cards(entry, current)
        entry["items"].sort(key=lambda triple: triple[0], reverse=True)

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
