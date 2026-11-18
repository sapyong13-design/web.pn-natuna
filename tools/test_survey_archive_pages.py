"""Kontrak halaman arsip laporan survei (SKM, IPAK, Survei Harian).

Yang dijaga:

1. **Semua tahun ditampilkan.** Halaman ini arsip, bukan kartu beranda: begitu 2026
   masuk, 2024 dan 2025 harus tetap terbaca. Tidak ada penyaringan tahun berjalan di
   sini - berbeda dengan kartu Kinerja dan Akuntabilitas yang hanya memuat tahun
   berjalan.

2. **Satu kartu per periode.** Berkas ganda untuk triwulan yang sama tidak boleh
   menghasilkan dua kartu; dulu halaman SKM memuat "Triwulan I 2026" dua kali.

3. **Urutan terbaru lebih dulu**, dan triwulan gabungan ("3 dan 4") menutup dua nomor
   sekaligus sehingga tidak dilaporkan sebagai celah.

4. **Keterangan celah memakai tanggal hari ini**, bukan tahun terbaru di Drive: tahun
   yang sudah lewat wajib lengkap, tahun berjalan hanya sampai periode yang selesai.

Jalankan: python tools/test_survey_archive_pages.py
"""
import importlib.util
import sys
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("refresh_surveys_under_test", ROOT / "tools" / "refresh-surveys.py")
rs = importlib.util.module_from_spec(spec)
spec.loader.exec_module(rs)

failures = []

def expect(condition, message):
    if not condition:
        failures.append(message)

# --- describe(): label dan kunci urutan ----------------------------------------
label, key = rs.describe("TRIWULAN", "1. LAPORAN SKM TRIWULAN 3 2026.pdf", "2026", "SKM")
expect(label == "Laporan SKM Triwulan III 2026", f"Label SKM salah: {label!r}")
expect(key == (2026, 3, 0), f"Kunci SKM salah: {key!r}")

label, key = rs.describe("TRIWULAN", "LAPORAN IPAK TRIWULAN 3 DAN 4 2024.pdf", "2024", "IPAK")
expect(label == "Laporan IPAK Triwulan III dan IV 2024", f"Label gabungan salah: {label!r}")
expect(key == (2024, 3, 4), f"Kunci gabungan salah: {key!r}")

label, key = rs.describe("BULANAN", "LAPORAN SURVEI HARIAN OKTOBER 2025.pdf", "2025")
expect(label == "Laporan Survei Harian Bulan Oktober 2025", f"Label harian salah: {label!r}")
expect(key == (2025, 10), f"Kunci harian salah: {key!r}")

# Nama yang tidak dikenali harus dilaporkan, bukan ditebak jadi kartu asal.
for kind, name in (("TRIWULAN", "LAPORAN SKM 2026.pdf"), ("BULANAN", "LAPORAN HARIAN 2026.pdf")):
    try:
        rs.describe(kind, name, "2026")
        expect(False, f"Nama tanpa periode harus ditolak: {name}")
    except ValueError:
        pass

# --- nomor triwulan Romawi -----------------------------------------------------
# Berkas lama memakai "TRIWULAN III"; tanpa dukungan ini sinkronisasi berhenti pada
# berkas pertama yang memakainya.
label, key = rs.describe("TRIWULAN", "LAPORAN SPAK TRIWULAN I 2022.pdf", "2022", "IPAK")
expect(label == "Laporan IPAK Triwulan I 2022", f"Romawi harus terbaca, dapat {label!r}")
expect(key[:2] == (2022, 1), f"Kunci triwulan Romawi salah: {key!r}")
label, key = rs.describe("TRIWULAN", "Laporan SPAK Triwulan III 2024.pdf", "2024", "IPAK")
expect(key[:2] == (2024, 3), f"Romawi III harus jadi 3: {key!r}")

# --- sinkronisasi bersifat aditif ----------------------------------------------
# Kartu yang sudah ada di artikel tapi tidak berasal dari folder yang dibaca harus
# dipertahankan; kalau tidak, memindahkan folder menghapus tautan yang belum ikut.
entry = {"kind": "FILE", "items": [], "years": {}}
existing = ('<section class="transparency-archive">'
            '<a class="transparency-document" href="https://drive.google.com/file/d/AAA/view" '
            'target="_blank" rel="noopener"><span><strong>LKjIP PN Natuna Tahun 2025</strong>'
            '<small>Google Drive</small></span></a></section>')
rs.keep_legacy_cards(entry, existing)
expect(len(entry["items"]) == 1, "Kartu lama harus dipertahankan.")
expect("LKjIP PN Natuna Tahun 2025" in entry["items"][0][2], "Label kartu lama harus utuh.")
# Berkas yang sama dari folder baru tidak boleh menghasilkan kartu ganda.
entry2 = {"kind": "FILE", "items": [(0, '<a href="https://drive.google.com/file/d/AAA/view">x</a>', "baru")], "years": {}}
rs.keep_legacy_cards(entry2, existing)
expect(len(entry2["items"]) == 1, "Kartu dengan href sama tidak boleh diduplikasi.")

# --- prettify(): nama berkas jadi judul yang enak dibaca -----------------------
expect(rs.prettify("1. LAYANAN PENGAJUAN PERMOHONAN KEPANITERAAN PIDANA.pdf")
       == "Layanan Pengajuan Permohonan Kepaniteraan Pidana",
       "Awalan nomor dan ekstensi dibuang, huruf besar dirapikan.")
expect(rs.prettify("LRA DIPA 01 dan 03  JULI 2026.pdf") == "LRA DIPA 01 dan 03 Juli 2026",
       "Nama bulan dinormalkan walau berkasnya tidak seluruhnya huruf besar.")
expect(rs.prettify("RENCANA AKSI 2025 02-18-2026-202814.pdf") == "Rencana Aksi 2025",
       "Stempel waktu ekspor di ujung nama dibuang.")
expect(rs.prettify("LHKPN_WKPN.pdf") == "LHKPN WKPN",
       "Garis bawah jadi spasi, singkatan tetap huruf besar.")

# --- month_in(): dua gaya penamaan bulan di Drive ------------------------------
expect(rs.month_in("LRA DIPA 01 dan 03 JULI 2026.pdf") == 7, "Nama bulan harus terbaca.")
expect(rs.month_in("LRA DIPA 01 dan 03_12-2025.pdf") == 12, "Pola bulan-tahun harus terbaca.")
expect(rs.month_in("Laporan tanpa bulan.pdf") == 0, "Tanpa bulan, hasilnya 0.")

# Urutan seksi FILE: bulan terbaru di atas dalam tahun yang sama.
labels = [rs.describe("FILE", n, "2026") for n in (
    "LRA DIPA 01 dan 03 Maret 2026.pdf",
    "LRA DIPA 01 dan 03 September 2026.pdf",
    "LRA DIPA 01 dan 03 Juni 2026.pdf",
)]
labels.sort(key=lambda pair: pair[1], reverse=True)
expect([label for label, _ in labels] == [
    "LRA DIPA 01 dan 03 September 2026",
    "LRA DIPA 01 dan 03 Juni 2026",
    "LRA DIPA 01 dan 03 Maret 2026",
], f"Urutan bulan harus terbaru dulu, dapat {[l for l, _ in labels]}.")

# --- konteks untuk berkas yang namanya tidak menyebut jenis/tahun --------------
# "Sutriadi.pdf" di folder LHKPN 2025 harus jadi kartu yang bisa dibaca tanpa
# membuka berkasnya.
label, _ = rs.describe("FILE", "Sutriadi.pdf", "2025", "", "LHKPN")
expect(label == "LHKPN Sutriadi 2025", f"Label LHKPN harus berkonteks, dapat {label!r}")
# Berkas yang sudah menyebut jenis dan tahun tidak diubah lagi.
label, _ = rs.describe("FILE", "LRA DIPA 01 dan 03 Juli 2026.pdf", "2026", "", "LHKPN")
expect(label == "LRA DIPA 01 dan 03 Juli 2026", f"Label yang sudah lengkap tidak boleh ditambah, dapat {label!r}")
# Awalan tidak digandakan bila nama berkas sudah memulainya.
label, _ = rs.describe("FILE", "LHKPN WKPN 2025.pdf", "2025", "", "LHKPN")
expect(label == "LHKPN WKPN 2025", f"Awalan tidak boleh dobel, dapat {label!r}")

# --- build_articles(): semua tahun, satu kartu per periode ---------------------
# Struktur Drive: induk -> kategori -> tahun -> berkas. 2024, 2025, dan 2026 harus
# semuanya ikut, bukan hanya tahun terbaru.
year_jobs = [
    ("Laporan SKM", "2024", "S2024"),
    ("Laporan SKM", "2025", "S2025"),
    ("Laporan SKM", "2026", "S2026"),
    ("Laporan IPAK", "2026", "I2026"),
]
year_files = {
    "S2024": [{"id": "a1", "name": "1. LAPORAN SKM TRIWULAN 1 2024.pdf", "folder": False},
              {"id": "a2", "name": "4. LAPORAN SKM TRIWULAN 4 2024.pdf", "folder": False}],
    "S2025": [{"id": "b1", "name": "1. LAPORAN SKM TRIWULAN 1 2025.pdf", "folder": False}],
    "S2026": [{"id": "c1", "name": "1. LAPORAN SKM TRIWULAN 1 2026.pdf", "folder": False},
              {"id": "c3", "name": "3. LAPORAN SKM TRIWULAN 3 2026.pdf", "folder": False}],
    "I2026": [{"id": "d1", "name": "LAPORAN IPAK TRIWULAN 1 2026.pdf", "folder": False}],
}
articles = rs.build_articles(year_jobs, year_files)

skm = articles[42]
expect(skm["kind"] == "TRIWULAN", "Artikel 42 harus memakai jenis TRIWULAN.")
expect(len(skm["items"]) == 5, f"Lima kartu SKM harus tersusun, dapat {len(skm['items'])}.")
expect(sorted(skm["years"]) == ["2024", "2025", "2026"],
       f"Semua tahun harus ikut, dapat {sorted(skm['years'])}.")
labels = [label for _, _, label in skm["items"]]
expect(labels == [
    "Laporan SKM Triwulan III 2026",
    "Laporan SKM Triwulan I 2026",
    "Laporan SKM Triwulan I 2025",
    "Laporan SKM Triwulan IV 2024",
    "Laporan SKM Triwulan I 2024",
], f"Urutan kartu harus terbaru dulu, dapat {labels}.")
expect(len(labels) == len(set(labels)), "Tidak boleh ada kartu ganda untuk periode yang sama.")
expect(articles[43]["kind"] == "TRIWULAN", "Artikel 43 harus memakai jenis TRIWULAN.")
expect(all("transparency-document" in markup for _, markup, _ in skm["items"]),
       "Setiap kartu harus memakai kelas .transparency-document.")
expect(all("&nearr;" not in markup for _, markup, _ in skm["items"]),
       "Panah tidak boleh ditulis sebagai entity; CSS yang menggambarnya.")

# --- _span(): rangkuman nomor yang hilang --------------------------------------
expect(rs._span([], rs.ROMAN) == "", "Daftar kosong tidak menghasilkan teks.")
expect(rs._span([1], rs.ROMAN) == "I", "Satu nomor ditulis apa adanya.")
expect(rs._span([1, 2], rs.ROMAN) == "I dan II", "Dua nomor berurutan memakai 'dan'.")
expect(rs._span([2, 3, 4], rs.ROMAN) == "II–IV", "Tiga nomor berurutan memakai rentang.")
expect(rs._span([1, 3], rs.ROMAN) == "I, III", "Nomor terpisah dipisah koma.")

# --- missing_note(): acuan tanggal hari ini -----------------------------------
today = date(2026, 11, 12)

# Tahun lewat wajib lengkap: 2025 yang hanya punya Januari–Oktober melaporkan sisanya.
note = rs.missing_note("BULANAN", {"2025": set(range(1, 11))}, today)
expect("November dan Desember 2025" in note, f"Celah tahun lewat harus dilaporkan: {note!r}")

# Tahun berjalan hanya sampai bulan yang sudah selesai (Oktober), bukan Desember.
note = rs.missing_note("BULANAN", {"2026": set(range(1, 10))}, today)
expect("Oktober 2026" in note, f"Celah tahun berjalan harus sampai bulan lalu: {note!r}")
expect("November" not in note and "Desember" not in note,
       "Bulan yang belum selesai tidak boleh disebut terlambat.")

# Triwulan: tahun berjalan hanya sampai triwulan yang sudah berakhir (III).
note = rs.missing_note("SKM", {"2026": {1, 3}}, today)
expect("Triwulan II 2026" in note, f"Celah triwulan harus dilaporkan: {note!r}")
expect("IV" not in note, "Triwulan IV 2026 belum berakhir, tidak boleh disebut.")

# Lengkap = tanpa keterangan.
expect(rs.missing_note("SKM", {"2026": {1, 2, 3}}, today) == "",
       "Tahun berjalan yang lengkap tidak butuh keterangan celah.")
expect(rs.missing_note("IPAK", {"2024": {1, 2, 3, 4}}, today) == "",
       "Tahun lewat yang lengkap tidak butuh keterangan celah.")

# Triwulan gabungan menutup dua nomor: "III dan IV 2024" tidak boleh jadi celah.
entry = rs.build_articles(
    [("Laporan IPAK", "2024", "X")],
    {"X": [{"id": "x", "name": "LAPORAN IPAK TRIWULAN 1 2024.pdf", "folder": False},
           {"id": "y", "name": "LAPORAN IPAK TRIWULAN 2 2024.pdf", "folder": False},
           {"id": "z", "name": "LAPORAN IPAK TRIWULAN 3 DAN 4 2024.pdf", "folder": False}]},
)
expect(rs.missing_note("IPAK", entry[43]["years"], today) == "",
       "Triwulan gabungan 3 dan 4 harus menutup kedua nomor.")

# --- folder yang diurus skrip lain tidak boleh dianggap belum dipetakan --------
# Dua folder beranda diurus skrip lain: Survei Terbaru (kartu Kinerja) dan
# Realisasi DIPA (widget Akuntabilitas). Keduanya bukan halaman Transparansi.
expect(rs.HANDLED_ELSEWHERE == {"Survei Terbaru", "Realisasi DIPA"},
       "Dua folder beranda diurus refresh-survey.py dan refresh-dipa.py.")
# Realisasi DIPA adalah seksi halaman Transparansi (artikel 40) sekaligus sumber
# widget Akuntabilitas di beranda; keduanya membaca folder Drive yang sama, jadi
# pengecualian ini disengaja. Seksi lain tidak boleh tumpang tindih.
expect(set(rs.SECTIONS) & rs.HANDLED_ELSEWHERE <= {"Realisasi DIPA"},
       "Hanya Realisasi DIPA yang boleh dipakai dua tempat.")
# Setiap seksi harus punya artikel, jenis, dan label yang masuk akal.
for name, config in rs.SECTIONS.items():
    expect(isinstance(config.get("article"), int), f"Seksi {name} tanpa id artikel.")
    expect(config.get("kind") in ("TRIWULAN", "BULANAN", "FILE"),
           f"Seksi {name} memakai jenis tak dikenal: {config.get('kind')!r}.")
    if config["kind"] == "TRIWULAN":
        expect(config.get("label"), f"Seksi triwulan {name} butuh label jenis (SKM/IPAK).")
# Tidak boleh ada dua seksi yang menulis artikel yang sama.
articles = [c["article"] for c in rs.SECTIONS.values()]
expect(len(articles) == len(set(articles)), f"Ada artikel yang dipakai dua seksi: {articles}.")

# --- year_from(): folder tahun boleh tetap membawa nama lamanya ---------------
# Folder yang dipindah manual sering tetap bernama "SAKIP 2020". Tanpa normalisasi,
# tahunnya hilang dan urutan serta label kartu rusak.
expect(rs.year_from("SAKIP 2020") == "2020", "Tahun harus terbaca dari nama folder apa adanya.")
expect(rs.year_from("2021") == "2021", "Folder bernama tahun saja juga harus terbaca.")
expect(rs.year_from("E-Brosur") == "", "Folder tanpa tahun menghasilkan string kosong.")
label, key = rs.describe("FILE", "RKT 2026.pdf", "SAKIP 2025")
expect(key[0] == 2026, f"Tahun folder 'SAKIP 2025' harus jadi 2025/2026, dapat {key[0]}.")
label, key = rs.describe("FILE", "Sutriadi.pdf", "LHKPN 2025", "", "LHKPN")
expect(label == "LHKPN Sutriadi 2025", f"Label LHKPN harus memakai tahun folder, dapat {label!r}.")

# --- grup Beranda/Transparansi ikut dipindai -----------------------------------
# Struktur kanonis: folder induk berisi `Beranda` dan `Transparansi`, seksi ada di
# dalamnya. Seksi yang belum dipindah tetap ditemukan di akar.
expect(rs.GROUPS == ("Transparansi", "Beranda"), "Dua grup kanonis harus dikenali.")

# --- folder_key(): nama folder dibuat manual, harus toleran --------------------
expect(rs.folder_key("E-Brosur") == rs.folder_key("E-brosur") == rs.folder_key("E brosur"),
       "Beda huruf besar dan tanda hubung tidak boleh membuat folder tak dikenali.")
expect(rs.folder_key("Ringkasan LKjIP") == rs.folder_key("ringkasan lkjip"),
       "Nama seksi harus dibandingkan tanpa peduli huruf besar.")
expect(rs.folder_key("Laporan  Survei   Harian") == rs.folder_key("Laporan Survei Harian"),
       "Spasi ganda tidak boleh membedakan folder.")

# Pencocokan toleran dipakai saat memilih sumber.
root_variant = [{"name": "E-brosur", "id": "EB", "folder": True},
                {"name": "ringkasan lkjip", "id": "LK", "folder": True}]
variant_sources = rs.section_sources(root_variant)
expect(("root", "", "EB") in variant_sources["E-Brosur"],
       "Folder bernama 'E-brosur' harus dikenali sebagai seksi E-Brosur.")
expect(("root", "", "LK") in variant_sources["Ringkasan LKjIP"],
       "Folder huruf kecil harus dikenali sebagai Ringkasan LKjIP.")

# --- section_sources(): folder induk dan lokasi lama digabung ------------------
root_entries = [
    {"name": "Realisasi DIPA", "id": "ROOT_DIPA", "folder": True},
    {"name": "Laporan SKM", "id": "ROOT_SKM", "folder": True},
    {"name": "Survei Terbaru", "id": "HOME", "folder": True},
    {"name": "berkas-lepas.pdf", "id": "F0", "folder": False},
]
sources = rs.section_sources(root_entries)
expect(("root", "", "ROOT_DIPA") in sources["Realisasi DIPA"],
       "Seksi yang subfoldernya ada harus memakai folder induk.")
expect([s for s in sources["Laporan SKM"]] == [("root", "", "ROOT_SKM")],
       "Seksi tanpa folder lama hanya memakai folder induk.")
expect("Survei Terbaru" not in sources, "Folder kartu beranda bukan seksi Transparansi.")
expect("Peraturan dan Kebijakan" not in sources,
       "Seksi tanpa sumber apa pun dilewati supaya artikelnya tidak dikosongkan.")

# Berkas yang muncul di folder induk dan folder lama hanya menjadi satu kartu.
dup_jobs = [("Laporan SKM", "2026", "ROOT_SKM_2026"), ("Laporan SKM", "2026", "OLD_SKM_2026")]
dup_files = {
    "ROOT_SKM_2026": [{"id": "same", "name": "1. LAPORAN SKM TRIWULAN 3 2026.pdf", "folder": False}],
    "OLD_SKM_2026": [{"id": "same", "name": "1. LAPORAN SKM TRIWULAN 3 2026.pdf", "folder": False}],
}
dup_articles = rs.build_articles(dup_jobs, dup_files)
expect(len(dup_articles[42]["items"]) == 1,
       f"Berkas yang sama tidak boleh jadi dua kartu, dapat {len(dup_articles[42]['items'])}.")

# --- keep_legacy_cards(): href lama tidak boleh menghentikan refresh ------------
# Produksi pernah memuat kartu dengan href tanpa slash. `href.rsplit("/", 2)[-2]`
# melempar IndexError dan seluruh refresh transparansi gagal (exit 1), padahal hanya
# satu kartu lama yang bentuknya tidak terduga.
expect(rs.file_id_from_href("https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view?usp=sharing")
       == "1AbCdEfGhIjKlMn", "Id Drive tidak terbaca dari URL lengkap.")
expect(rs.file_id_from_href("laporan.pdf") == "laporan.pdf",
       "href tanpa slash tidak boleh melempar; dipakai apa adanya.")
expect(rs.file_id_from_href("/files/laporan.pdf") == "laporan.pdf",
       "href relatif memakai segmen terakhir.")
expect(rs.file_id_from_href("") == "", "href kosong tidak boleh melempar.")

legacy_entry = {"kind": "FILE", "items": [], "years": {}}
legacy_html = (
    '<section class="transparency-archive">'
    '<a class="transparency-document" href="laporan-lama.pdf"><span><strong>Laporan Lama 2024</strong></span></a>'
    '<a class="transparency-document" href="https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view">'
    '<span><strong>Laporan Drive 2025</strong></span></a>'
    "</section>"
)
rs.keep_legacy_cards(legacy_entry, legacy_html)
expect(len(legacy_entry["items"]) == 2,
       f"Kartu lama yang sah harus dipertahankan, dapat {len(legacy_entry['items'])}.")

# --- refresh-survey.py: nama konstanta wajib ada -------------------------------
# `main()` memakai nama modul sebagai variabel global. Satu salah nama
# (`ROOT_FOLDER_ID` alih-alih `CONTAINER_ID`) tidak tertangkap saat impor karena
# baru dievaluasi di dalam fungsi, lalu `except Exception` di `__main__` menelannya
# menjadi "[ERROR] Refresh survei gagal" tanpa jejak. Cron per jam karenanya gagal
# di tahap [2/4] setiap kali berjalan sejak 12 November 2026.
survey_spec = importlib.util.spec_from_file_location(
    "refresh_survey_under_test", ROOT / "tools" / "refresh-survey.py")
rsv = importlib.util.module_from_spec(survey_spec)
survey_spec.loader.exec_module(rsv)

# Nama global yang dirujuk main() harus benar-benar ada di modul.
import ast
source = (ROOT / "tools" / "refresh-survey.py").read_text(encoding="utf-8")
tree = ast.parse(source)
main_fn = next((n for n in ast.walk(tree)
                if isinstance(n, ast.FunctionDef) and n.name == "main"), None)
expect(main_fn is not None, "main() tidak ditemukan di refresh-survey.py")
if main_fn is not None:
    # Nama yang diikat di mana pun di dalam fungsi: argumen, target assign, variabel
    # loop (for/tuple), nama `with`, dan target `except ... as`. Tanpa ini variabel
    # loop seperti `period` salah dilaporkan sebagai tidak terdefinisi.
    bound = {a.arg for a in main_fn.args.args}

    def collect_targets(node):
        for t in ast.walk(node):
            if isinstance(t, ast.Name) and isinstance(t.ctx, (ast.Store, ast.Del)):
                bound.add(t.id)
            elif isinstance(t, ast.arg):
                bound.add(t.arg)

    collect_targets(main_fn)
    import builtins
    used = {n.id for n in ast.walk(main_fn)
            if isinstance(n, ast.Name) and isinstance(n.ctx, ast.Load)}
    undefined = sorted(name for name in used
                       if name not in bound
                       and not hasattr(rsv, name)
                       and not hasattr(builtins, name))
    expect(not undefined,
           f"main() merujuk nama yang tidak ada di modul: {undefined}")

# --- refresh-survey.py: folder tahun dicari lewat folder seksi -----------------
# Folder tahun tidak berada di akar Drive; strukturnya
# `WEB PN-NATUNA.GO.ID/Beranda/Survei Terbaru/<tahun>`. Skrip sempat memanggil
# `list_year_folders(CONTAINER_ID)` sehingga tidak menemukan satu pun folder tahun
# dan keluar 1 setiap kali cron berjalan. Kontraknya: main() wajib memakai
# resolve_section() lebih dulu, bukan membaca akar.
if main_fn is not None:
    calls = {n.func.id for n in ast.walk(main_fn)
             if isinstance(n, ast.Call) and isinstance(n.func, ast.Name)}
    expect("resolve_section" in calls,
           "main() harus memanggil resolve_section() untuk menemukan folder seksi.")
    expect("list_year_folders" in calls,
           "main() harus memanggil list_year_folders() pada folder seksi.")
    # list_year_folders(CONTAINER_ID) langsung dari akar adalah bug lama.
    root_reads = [
        n for n in ast.walk(main_fn)
        if isinstance(n, ast.Call)
        and isinstance(n.func, ast.Name) and n.func.id == "list_year_folders"
        and n.args and isinstance(n.args[0], ast.Name) and n.args[0].id == "CONTAINER_ID"
    ]
    expect(not root_reads,
           "list_year_folders(CONTAINER_ID) membaca akar Drive; pakai id folder seksi.")

# resolve_section(): normalisasi nama harus toleran spasi/tanda baca.
expect(hasattr(rsv, "resolve_section"), "refresh-survey harus menyediakan resolve_section().")
expect(rsv.SECTION_NAME == "Survei Terbaru",
       f"SECTION_NAME salah: {rsv.SECTION_NAME!r}")
expect(tuple(rsv.GROUPS) == ("Beranda", "Transparansi"),
       f"GROUPS salah: {rsv.GROUPS!r}")

if failures:
    sys.stderr.write("\n".join(failures) + "\n")
    sys.exit(1)
print("Survey archive pages: OK")
