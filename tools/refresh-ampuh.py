#!/usr/bin/env python3
"""Sinkronkan dataset direktori AMPUH 2026 dengan folder Google Drive.

Renderer `ampuh-directory.php` membaca JSON statis dan tidak pernah menyentuh
jaringan, jadi daftar dokumen hanya berubah kalau ada yang menulis ulang JSON.
Skrip ini melakukannya langsung dari Drive, sekali jalan, lalu hasilnya disimpan.

Tiga hal yang menentukan benar-tidaknya hasil:

1. Pencocokan per nomor, bukan per posisi.
   Folder Drive bernama "3. ..." dan dataset menyimpannya sebagai sub "2.3".
   Mencocokkan berdasarkan urutan membuat semua pasangan bergeser begitu ada
   satu folder baru disisipkan di tengah.

2. Rekursi ke subfolder.
   Sebagian checklist menaruh berkas di subfolder bertingkat (sub 2.3 berisi
   folder "ARSIP SKP PEGAWAI 2025" dengan ratusan PDF di dalamnya). Ekspor XLSX
   lama sudah meratakan ini; pembacaan satu level akan menganggap ratusan berkas
   itu hilang.

3. Urutan lama dipertahankan.
   Urutan berasal dari sheet 'Detail File' yang dikurasi manual dan sering
   dikelompokkan per tahun, bukan alfabetis. Berkas baru ditambahkan di akhir.

Jalankan tanpa `--write` untuk melihat rencana perubahan saja.
"""

from __future__ import annotations

import argparse
import html
import json
import re
import sys
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

DEFAULT_JSON = Path(__file__).resolve().parent.parent / "templates/pn_natuna_2026/data/ampuh-2026.json"
MAIN_FOLDER_ID = "1x6yBB_YxHRKGsuxgkN1enrWXiV3P2NWH"
FOLDER_URL = "https://drive.google.com/drive/folders/{id}?usp=sharing"
FOLDER_UI = "https://drive.google.com/drive/folders/{id}"
VIEW_URL = "https://drive.google.com/embeddedfolderview?id={id}#list"
UA = "Mozilla/5.0 (compatible; pn-natuna-ampuh-sync)"
MAX_DEPTH = 4

ENTRY_RE = re.compile(
    r'<div class="flip-entry"[^>]*id="entry-([^"]+)"[^>]*>.*?flip-entry-title">([^<]+)<',
    re.S,
)
# Folder dan berkas memakai markup yang sama; satu-satunya pembeda andal adalah
# aria-label pada ikon di dalam blok entri itu sendiri. Menebak dari ada-tidaknya
# tautan /drive/folders/ gagal untuk folder bersama seperti "HUKUM", dan menebak
# dari ekstensi nama gagal untuk folder yang namanya kebetulan berakhiran .pdf.
FOLDER_MARK = 'aria-label="Folder"'
BLOCK_SPLIT = '<div class="flip-entry"'


def natural_key(value: str) -> list:
    """Urutkan '10.' setelah '9.', bukan setelah '1.'."""
    return [int(part) if part.isdigit() else part.lower() for part in re.split(r"(\d+)", value)]


def leading_number(name: str) -> int | None:
    match = re.match(r"\s*(\d+)", name)
    return int(match.group(1)) if match else None


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):  # noqa: D401 - urllib hook
        return None


_NO_REDIRECT_OPENER = urllib.request.build_opener(_NoRedirect)


def resolve_folder(folder_id: str) -> str:
    """Ikuti shortcut Drive ke folder aslinya.

    Sebagian subfolder di AMPUH adalah shortcut ke folder lain. Drive membalas
    302 ke ID asli untuk `/drive/folders/<id>`, sedangkan embeddedfolderview
    tidak mengikuti shortcut itu dan mengembalikan 0 entri — bedanya penting,
    karena 0 entri membuat daftar lama tampak "sudah dihapus".
    """
    request = urllib.request.Request(
        FOLDER_UI.format(id=folder_id), headers={"User-Agent": UA}
    )
    try:
        with _NO_REDIRECT_OPENER.open(request, timeout=30) as response:
            return folder_id
    except urllib.error.HTTPError as error:
        location = error.headers.get("Location") or ""
        match = re.search(r"/folders/([A-Za-z0-9_-]{20,})", location)
        return match.group(1) if match else folder_id
    except Exception:
        return folder_id


def fetch_folder(folder_id: str, retries: int = 3) -> tuple[list[tuple[str, str, bool]], set[str]]:
    """Kembalikan (daftar (id, nama, apakah_folder), himpunan id folder)."""
    last: Exception | None = None
    for _ in range(retries):
        try:
            request = urllib.request.Request(
                VIEW_URL.format(id=folder_id), headers={"User-Agent": UA}
            )
            with urllib.request.urlopen(request, timeout=45) as response:
                page = response.read().decode("utf-8", "replace")
            entries: list[tuple[str, str, bool]] = []
            for block in page.split(BLOCK_SPLIT)[1:]:
                found = ENTRY_RE.search(BLOCK_SPLIT + block)
                if not found:
                    continue
                # Drive meng-escape nama ("LHP UMUM &amp; KEUANGAN"); tanpa unescape,
                # berkas lama tampak hilang lalu ditambahkan ulang sebagai duplikat.
                entry_id, name = found.group(1), html.unescape(found.group(2))
                entries.append((entry_id, name, FOLDER_MARK in block))
            folders = {entry_id for entry_id, _, is_folder in entries if is_folder}
            return entries, folders
        except Exception as exc:  # Drive kadang menutup koneksi di tengah jalan
            last = exc
    raise RuntimeError(f"Gagal membaca folder {folder_id}: {last}")


def flatten(folder_id: str, seen: set[str] | None = None, depth: int = 0) -> tuple[list[str], bool]:
    """Ratakankan isi folder, termasuk subfolder bertingkat.

    Mengembalikan (nama berkas, ada_subfolder_tak_terbaca). Penanda kedua penting:
    sebagian subfolder adalah shortcut ke folder yang tidak dibagikan publik, dan
    embeddedfolderview membalas 0 entri untuk itu — bukan berarti foldernya kosong.
    """
    seen = seen if seen is not None else set()
    if folder_id in seen or depth > MAX_DEPTH:
        return [], False
    seen.add(folder_id)
    entries, _ = fetch_folder(folder_id)
    unreadable = False
    if not entries:
        # 0 entri bisa berarti shortcut ke folder lain; coba folder aslinya.
        resolved = resolve_folder(folder_id)
        if resolved != folder_id:
            if resolved in seen:
                return [], False
            seen.add(resolved)
            entries, _ = fetch_folder(resolved)
            # Shortcut yang targetnya tetap kosong berarti tidak bisa dibaca,
            # bukan kosong sungguhan.
            unreadable = not entries
    names: list[str] = []
    for entry_id, name, is_folder in entries:
        if is_folder:
            child, child_unreadable = flatten(entry_id, seen, depth + 1)
            names.extend(child)
            unreadable = unreadable or child_unreadable
        else:
            names.append(name)
    return names, unreadable


def merge_files(existing: list[str], live: list[str]) -> tuple[list[str], list[str], list[str]]:
    """Pertahankan urutan lama, buang yang hilang, tambahkan yang baru di akhir.

    Nama dinormalisasi lebih dulu: dataset lama menyimpan sebagian nama dalam
    bentuk ter-escape ("LHP UMUM &amp; KEUANGAN") dan sebagian tidak. Tanpa
    normalisasi, satu berkas yang sama dianggap dua berkas berbeda.
    """
    live_norm = {html.unescape(name) for name in live}
    kept: list[str] = []
    seen_names: set[str] = set()
    removed: list[str] = []
    for name in existing:
        canonical = html.unescape(name)
        if canonical not in live_norm:
            removed.append(name)
        elif canonical not in seen_names:
            kept.append(canonical)
            seen_names.add(canonical)
    added = sorted(
        (html.unescape(n) for n in live if html.unescape(n) not in seen_names),
        key=natural_key,
    )
    return kept + added, added, removed


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--json", type=Path, default=DEFAULT_JSON)
    parser.add_argument("--write", action="store_true", help="tulis hasil ke JSON")
    args = parser.parse_args()

    raw = args.json.read_bytes().decode("utf-8")
    dataset = json.loads(raw)

    print("[1/4] Folder utama AMPUH...")
    top, top_folders = fetch_folder(MAIN_FOLDER_ID)
    by_number: dict[int, str] = {}
    for entry_id, name, is_folder in top:
        if not is_folder:
            continue
        number = leading_number(name)
        if number is not None:
            by_number.setdefault(number, entry_id)
    print(f"      {len(by_number)} folder checklist bernomor.")

    unique: dict[int, dict] = {}
    for gobi in dataset["gobis"]:
        for checklist in gobi["checklists"]:
            unique.setdefault(int(checklist["number"]), checklist)

    missing = sorted(set(unique) - set(by_number))
    extra = sorted(set(by_number) - set(unique))
    if missing:
        print(f"      [WARN] checklist tanpa folder Drive: {missing}", file=sys.stderr)
    if extra:
        print(f"      [WARN] folder Drive belum ada di dataset: {extra}", file=sys.stderr)

    targets = sorted(set(unique) & set(by_number))

    print("[2/4] Sub-checklist...")
    with ThreadPoolExecutor(max_workers=10) as pool:
        sub_entries = dict(zip(targets, pool.map(lambda n: fetch_folder(by_number[n]), targets)))

    jobs: list[str] = []
    for number in targets:
        entries, folders = sub_entries[number]
        jobs.extend(eid for eid, _, is_folder in entries if is_folder)
    print(f"      {len(jobs)} folder sub-checklist.")

    print("[3/4] Daftar berkas (rekursif)...")
    with ThreadPoolExecutor(max_workers=10) as pool:
        files_by_sub = dict(zip(jobs, pool.map(flatten, jobs)))
    print(f"      {len(files_by_sub)} folder dibaca.")

    added_total = removed_total = new_subs = 0
    unreadable_subs: list[str] = []

    # Satu nomor checklist bisa muncul di lebih dari satu gobi (6, 31, 44), dan
    # salinan-salinan itu tidak selalu berisi daftar yang sama. Jadi setiap
    # salinan diperbarui sendiri, bukan hanya kemunculan pertama.
    for gobi in dataset["gobis"]:
        for checklist in gobi["checklists"]:
            number = int(checklist["number"])
            if number not in by_number:
                continue
            checklist["drive_url"] = FOLDER_URL.format(id=by_number[number])

            entries, folders = sub_entries[number]
            sub_folders = [(eid, name) for eid, name, is_folder in entries if is_folder]
            existing = {str(s["number"]): s for s in checklist.get("subchecklists", [])}

            rebuilt: list[dict] = []
            used: set[str] = set()
            for position, (sub_id, sub_name) in enumerate(sub_folders, start=1):
                suffix = leading_number(sub_name) or position
                label = f"{number}.{suffix}"
                if label in used:  # dua folder dengan nomor sama
                    label = f"{number}.{position}"
                used.add(label)
                live, unreadable = files_by_sub.get(sub_id, ([], False))
                sub = existing.get(label)
                if sub is None:
                    sub = {"number": label, "title": sub_name, "drive_url": "", "files": []}
                    new_subs += 1
                sub["drive_url"] = FOLDER_URL.format(id=sub_id)
                if unreadable and sub.get("files"):
                    # Sebagian isi folder tidak terbaca anonim; jangan buang daftar lama.
                    kept_old = list(sub["files"])
                    live = kept_old + [n for n in live if n not in set(kept_old)]
                    if label not in unreadable_subs:
                        unreadable_subs.append(label)
                merged, added, removed = merge_files(list(sub.get("files", [])), live)
                sub["files"] = merged
                added_total += len(added)
                removed_total += len(removed)
                rebuilt.append(sub)

            rebuilt.sort(key=lambda s: natural_key(s["number"]))
            checklist["subchecklists"] = rebuilt

    # Nomor checklist yang muncul di beberapa gobi menunjuk folder Drive yang sama.
    # Kalau salah satu salinan tidak punya daftar (mis. folder privat), ambil dari
    # salinan lain yang punya — isinya dokumen yang sama.
    best: dict[str, list[str]] = {}
    for gobi in dataset["gobis"]:
        for checklist in gobi["checklists"]:
            for sub in checklist.get("subchecklists", []):
                if len(sub.get("files", [])) > len(best.get(str(sub["number"]), [])):
                    best[str(sub["number"])] = list(sub["files"])
    filled = 0
    for gobi in dataset["gobis"]:
        for checklist in gobi["checklists"]:
            for sub in checklist.get("subchecklists", []):
                if not sub.get("files") and best.get(str(sub["number"])):
                    sub["files"] = list(best[str(sub["number"])])
                    filled += 1
    if filled:
        print(f"      [INFO] {filled} sub kosong diisi dari salinan gobi lain.")

    print("[4/4] Selesai.")
    print(f"      berkas baru: {added_total}, berkas hilang: {removed_total}, sub baru: {new_subs}")
    if unreadable_subs:
        print(f"      [INFO] {len(unreadable_subs)} sub berisi folder privat; daftar lama dipertahankan: "
              f"{', '.join(unreadable_subs[:12])}{' ...' if len(unreadable_subs) > 12 else ''}")

    output = json.dumps(dataset, ensure_ascii=False, indent=2).replace("\n", "\r\n") + "\r\n"
    if not args.write:
        print(f"      {'ADA' if output != raw else 'TIDAK ADA'} perubahan. Pakai --write untuk menyimpan.")
        return 0

    args.json.write_bytes(output.encode("utf-8"))
    print(f"      Ditulis: {args.json} ({len(output.encode('utf-8'))} byte)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
