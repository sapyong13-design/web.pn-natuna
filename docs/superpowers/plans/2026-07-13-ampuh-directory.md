# Direktori AMPUH 2026 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun direktori publik AMPUH 2026 di Joomla PN Natuna dari workbook, dengan 82 checklist besar, sub-checklist, daftar file, pencarian global, filter GOBI, dan tombol folder Google Drive pada setiap tingkat folder.

**Architecture:** Importer Python standard-library membaca OOXML secara aman menjadi dataset PHP deterministik. Renderer artikel Joomla khusus memuat dataset dan menghasilkan disclosure semantik. JavaScript template menangani pencarian, filter, serta state disclosure; CSS template memakai token dan responsif situs utama. Migrasi idempotent membuat artikel dan menu canonical untuk subdomain/route AMPUH.

**Tech Stack:** Python 3 standard library (`zipfile`, `xml.etree.ElementTree`, `json`), PHP 8.3, Joomla 6.1, MySQL 8.4, HTML/CSS/JavaScript tanpa dependency baru.

## Global Constraints

- Publik tanpa login; seluruh nama file dan URL view-only dianggap publik.
- Tepat 82 checklist besar, bernomor 1 sampai 82.
- Hierarki: GOBI → checklist besar → sub-checklist → nama file.
- Tombol Drive tersedia pada folder utama, setiap checklist besar, dan setiap sub-checklist; URL kosong dirender sebagai label nonaktif.
- Tidak menampilkan progres, status upload, status print, catatan, atau tindak lanjut.
- Semua disclosure tertutup pada load normal; pencarian boleh membuka jalur hasil.
- Tidak menambah package Python, Composer, atau npm.
- Seluruh teks workbook di-escape sebelum dirender.

---

### Task 1: Importer dan kontrak dataset AMPUH

**Files:**
- Create: `tools/import-ampuh-checklist.py`
- Create: `tools/test_import_ampuh_checklist.py`
- Create: `templates/pn_natuna_2026/data/ampuh-2026.json`

**Interfaces:**
- Consumes: path workbook `.xlsx`; opsi `--output`; OOXML sheet utama dan `Detail File`.
- Produces: fungsi Python `parse_workbook(path: Path) -> dict`, `validate_dataset(data: dict) -> list[str]`, dan JSON UTF-8 dengan keys `title`, `main_drive_url`, `summary`, `gobis`.
- Dataset `gobis[]`: `number`, `name`, `checklists`.
- Dataset `checklists[]`: `number`, `title`, `drive_url`, `subchecklists`.
- Dataset `subchecklists[]`: `number`, `title`, `document_count`, `drive_url`, `files`.

- [ ] **Step 1: Tulis failing tests importer**

Buat workbook OOXML fixture minimal langsung dengan `zipfile` pada test, tanpa dependency eksternal. Fixture harus mencakup sel merged/blank, dua GOBI, checklist `1` dan `2`, sub-checklist `1.1`, `1.2`, `2.1`, detail file yang lebih panjang dari ringkasan sheet utama, dan ekstensi campuran.

```python
class AmpuhImporterTests(unittest.TestCase):
    def test_inherits_merged_parent_cells_and_builds_compound_numbers(self):
        data = IMPORTER.parse_workbook(self.fixture)
        self.assertEqual(data["gobis"][0]["checklists"][0]["number"], 1)
        self.assertEqual(
            [item["number"] for item in data["gobis"][0]["checklists"][0]["subchecklists"]],
            ["1.1", "1.2"],
        )

    def test_detail_sheet_replaces_truncated_file_summary(self):
        data = IMPORTER.parse_workbook(self.fixture)
        files = data["gobis"][0]["checklists"][0]["subchecklists"][0]["files"]
        self.assertEqual(files, ["Bukti A.pdf", "Rekap B.xlsx"])

    def test_validation_rejects_missing_or_duplicate_checklist_numbers(self):
        errors = IMPORTER.validate_dataset({"gobis": [{"checklists": [
            {"number": 1, "subchecklists": []}, {"number": 1, "subchecklists": []}
        ]}]})
        self.assertTrue(any("duplicate" in error.lower() for error in errors))
```

- [ ] **Step 2: Jalankan test dan pastikan gagal**

Run:

```bash
python tools/test_import_ampuh_checklist.py
```

Expected: `FileNotFoundError` atau import failure untuk `tools/import-ampuh-checklist.py`.

- [ ] **Step 3: Implementasikan parser OOXML minimal**

Implementasikan pembacaan `xl/workbook.xml`, relasi workbook, `sharedStrings.xml`, worksheet XML, serta nilai inline/shared string. Jangan membuka formula atau macro. Normalisasi whitespace hanya pada tepi; pertahankan nama file internal.

```python
def parse_workbook(path: Path) -> dict:
    with zipfile.ZipFile(path) as archive:
        sheets = load_sheets(archive)
        rows = read_rows(archive, sheets["AMPUH 2026 Checklist"])
        details = read_rows(archive, sheets["Detail File"])
    return build_dataset(rows, details)
```

Gunakan forward-fill hanya untuk GOBI, nomor checklist besar, dan judul checklist besar. Jangan forward-fill nomor sub-checklist. Detail file dikelompokkan dengan `(checklist_number, sub_number)`.

- [ ] **Step 4: Tambahkan validasi penuh**

`validate_dataset()` harus memeriksa:

```python
numbers == list(range(1, 83))
len(numbers) == len(set(numbers))
sub["number"] == f'{checklist["number"]}.{sub_index}'
sub["document_count"] == len(sub["files"])
```

Perbedaan count harus dilaporkan ke stderr dan membuat command exit nonzero; data tidak boleh dipotong.

- [ ] **Step 5: Jalankan test importer**

Run:

```bash
python tools/test_import_ampuh_checklist.py
```

Expected: `Ran ... tests ... OK`.

- [ ] **Step 6: Generate dataset dari workbook aktual**

Run:

```bash
python tools/import-ampuh-checklist.py "C:/Users/faris/Downloads/ampuh-checklist-2026-merged (1).xlsx" --output templates/pn_natuna_2026/data/ampuh-2026.json
```

Expected: ringkasan mencetak `82 checklists`, jumlah sub-checklist hasil parsing, jumlah file hasil detail, dan exit code 0. URL `main_drive_url`, checklist `drive_url`, serta sub-checklist `drive_url` masih string kosong.

- [ ] **Step 7: Commit task**

```bash
git add tools/import-ampuh-checklist.py tools/test_import_ampuh_checklist.py templates/pn_natuna_2026/data/ampuh-2026.json
git commit -m "feat: import AMPUH 2026 checklist data"
```

---

### Task 2: Renderer Joomla dan route canonical

**Files:**
- Create: `templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php`
- Modify: `templates/pn_natuna_2026/html/com_content/article/default.php:16-25`
- Create: `database/migrations/20260716_ampuh_directory.sql`
- Create: `tools/test_ampuh_directory_renderer.php`

**Interfaces:**
- Consumes: `$item` Joomla; JSON dari `templates/pn_natuna_2026/data/ampuh-2026.json`, dibaca dengan `file_get_contents` lalu `json_decode(..., true, 512, JSON_THROW_ON_ERROR)`.
- Produces: renderer yang mengembalikan `true` hanya untuk artikel canonical AMPUH; markup root `.ampuh-directory[data-ampuh-directory]`.
- Migrasi menghasilkan artikel alias `ampuh-2026` dan menu alias `ampuh` secara idempotent tanpa hard-code ID baru.

- [ ] **Step 1: Tulis failing renderer contract**

```php
$source = file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php');
$dispatcher = file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$expect(str_contains($source, 'data-ampuh-directory'), 'Missing AMPUH root hook.');
$expect(str_contains($source, 'Buka Folder Utama AMPUH 2026'), 'Missing main Drive action.');
$expect(str_contains($source, 'aria-expanded="false"'), 'Disclosures must start closed.');
$expect(str_contains($source, 'rel="noopener noreferrer"'), 'Drive links must be isolated.');
$expect(str_contains($dispatcher, "require __DIR__ . '/ampuh-directory.php'"), 'Missing dispatcher.');
```

Tambahkan checks untuk input pencarian, filter GOBI, result status live region, tombol tutup semua, tombol folder checklist, tombol folder sub-checklist, empty URL label, dan escaping.

- [ ] **Step 2: Jalankan test dan pastikan gagal**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php
```

Expected: missing renderer failure.

- [ ] **Step 3: Implementasikan renderer**

Dispatcher harus dijalankan sebelum transparency/news rendering:

```php
if (require __DIR__ . '/ampuh-directory.php') {
    return;
}
```

Renderer harus mengenali alias canonical, memuat dataset dengan `require`, memvalidasi bentuk dasar, dan memakai helper:

```php
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$driveAction = static function (string $url, string $label) use ($escape): void {
    if ($url === '') {
        echo '<span class="ampuh-directory__drive-unavailable">Tautan belum tersedia</span>';
        return;
    }
    echo '<a class="ampuh-directory__drive" href="' . $escape($url) . '" target="_blank" rel="noopener noreferrer">' . $escape($label) . '</a>';
};
```

Gunakan `<button type="button" aria-expanded="false" aria-controls="...">` dan panel `[hidden]`, bukan `<details>`, agar pencarian dapat membuka/menutup state deterministik. Berikan `data-search-text` yang sudah dinormalisasi server-side pada node GOBI/checklist/sub-checklist/file.

- [ ] **Step 4: Buat migrasi idempotent**

`20260716_ampuh_directory.sql` harus upsert artikel dan menu menggunakan alias, bukan ID tetap. Konten artikel hanya shell pendek karena renderer memiliki tampilan. Menu harus publik, published, dan route ke artikel canonical. Gunakan pola `INSERT ... SELECT ... WHERE NOT EXISTS` lalu `UPDATE` canonical.

- [ ] **Step 5: Jalankan renderer contract**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php
```

Expected: `AMPUH directory renderer contract: ok`.

- [ ] **Step 6: Jalankan migrasi ke DB lokal dan cek route**

Run:

```bash
python tools/apply-db-migrations.py --database pn_natuna_rebuild --mysql C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe
curl.exe -I --max-time 15 http://127.0.0.1:8080/ampuh
```

Expected: migration applied once; route returns `HTTP/1.1 200 OK`.

- [ ] **Step 7: Commit task**

```bash
git add templates/pn_natuna_2026/html/com_content/article/default.php templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php database/migrations/20260716_ampuh_directory.sql tools/test_ampuh_directory_renderer.php
git commit -m "feat: render AMPUH document directory"
```

---

### Task 3: Pencarian, filter, dan disclosure behavior

**Files:**
- Modify: `templates/pn_natuna_2026/js/template.js:1-145`
- Create: `tools/test_ampuh_directory_interactions.py`

**Interfaces:**
- Consumes: markup `[data-ampuh-directory]`, `[data-ampuh-toggle]`, `[data-ampuh-panel]`, `[data-ampuh-search]`, `[data-ampuh-gobi-filter]`.
- Produces: `setupAmpuhDirectory()` yang aman no-op pada halaman lain.

- [ ] **Step 1: Tulis failing static/behavior contract**

Test harus memastikan fungsi dan event hooks tersedia serta tidak memakai penyimpanan browser:

```python
for token in [
    "setupAmpuhDirectory()",
    "data-ampuh-search",
    "data-ampuh-gobi-filter",
    "aria-expanded",
    "ampuh-directory__empty",
]:
    assert token in source, token
assert "localStorage" not in ampuh_function_source
assert "sessionStorage" not in ampuh_function_source
```

Tambahkan fixture DOM browser pada Task 5 untuk kontrak perilaku nyata; test ini menjaga wiring statis awal.

- [ ] **Step 2: Jalankan test dan pastikan gagal**

Run:

```bash
python tools/test_ampuh_directory_interactions.py
```

Expected: assertion failure untuk `setupAmpuhDirectory()`.

- [ ] **Step 3: Implementasikan disclosure state**

Tambahkan `setupAmpuhDirectory();` dalam initializer. Fungsi `setExpanded(toggle, expanded)` wajib menyinkronkan `aria-expanded` dan `hidden` panel. Tombol `Tutup Semua` memanggil fungsi tersebut untuk semua toggle.

- [ ] **Step 4: Implementasikan pencarian dan filter**

Normalisasi query dengan `toLocaleLowerCase('id-ID').trim()`. Item cocok bila teks node atau descendant cocok. Saat query aktif, buka ancestor jalur hasil. Filter GOBI dan query memakai irisan. Live region menampilkan jumlah sub-checklist/file cocok atau `Tidak ada dokumen yang cocok.`

Saat query kosong:

```javascript
items.forEach((item) => { item.hidden = false; });
toggles.forEach((toggle) => setExpanded(toggle, false));
```

Jangan menyimpan state dan jangan mengubah URL.

- [ ] **Step 5: Jalankan interaction contract**

Run:

```bash
python tools/test_ampuh_directory_interactions.py
```

Expected: `AMPUH directory interaction contract: ok`.

- [ ] **Step 6: Commit task**

```bash
git add templates/pn_natuna_2026/js/template.js tools/test_ampuh_directory_interactions.py
git commit -m "feat: add AMPUH directory search"
```

---

### Task 4: Visual system, dark mode, dan responsive layout

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css` (append section `AMPUH DIRECTORY 2026-07-13`)
- Modify: `tools/test_ampuh_directory_renderer.php`

**Interfaces:**
- Consumes: `.ampuh-directory*` markup dari Task 2.
- Produces: desktop editorial layout, mobile one-column at `760px`, dark mode, focus-visible, reduced-motion.

- [ ] **Step 1: Perluas failing CSS contract**

Tambahkan checks:

```php
foreach ([
    'AMPUH DIRECTORY 2026-07-13',
    '.ampuh-directory__hero',
    '.ampuh-directory__gobi',
    '.ampuh-directory__checklist',
    '.ampuh-directory__subchecklist',
    'body.is-dark .ampuh-directory',
    '@media (max-width: 760px)',
    '@media (prefers-reduced-motion: reduce)',
    ':focus-visible',
] as $token) {
    $expect(str_contains($css, $token), "Missing CSS contract {$token}.");
}
```

- [ ] **Step 2: Jalankan contract dan pastikan gagal**

Run renderer contract. Expected: missing CSS contract failures.

- [ ] **Step 3: Implementasikan style editorial**

Gunakan token yang sudah ada: `--color-primary`, `--color-accent`, `--color-ink`, `--color-muted`, `--color-soft`, `--shadow-subtle/card`, font Fraunces untuk heading dan Plus Jakarta Sans untuk body. Bedakan tingkat hierarki dengan nomor, skala, background tint, dan indentasi; jangan memakai side-stripe atau nested card grid.

Hero harus punya satu aksi utama yang dominan. Ringkasan inventaris berupa baris editorial, bukan hero-metric cards. File list berupa daftar rapat yang mudah dipindai.

- [ ] **Step 4: Implementasikan state dan responsive**

Panel `[hidden]` tidak mengambil ruang. Target tombol/link minimal 44px. Mobile satu kolom, metadata wrap, URL action full-width bila perlu, nama file `overflow-wrap:anywhere`. Dark mode menggunakan neutral tinted yang konsisten. Motion hanya opacity/transform dan dimatikan pada reduced motion.

- [ ] **Step 5: Jalankan renderer contract**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php
```

Expected: `AMPUH directory renderer contract: ok`.

- [ ] **Step 6: Commit task**

```bash
git add templates/pn_natuna_2026/css/template.css tools/test_ampuh_directory_renderer.php
git commit -m "style: present AMPUH directory hierarchy"
```

---

### Task 5: End-to-end verification dan deployment handoff

**Files:**
- Create: `tools/test_ampuh_directory_e2e.py`
- Modify: `HANDOFF.md`

**Interfaces:**
- Consumes: local route `http://127.0.0.1:8080/ampuh`, current MySQL database, dan `templates/pn_natuna_2026/data/ampuh-2026.json`.
- Produces: deterministic dataset assertions plus browser QA contract.

- [ ] **Step 1: Tulis failing E2E assertions**

Gunakan browser automation yang tersedia di harness untuk memverifikasi contract nyata. Test helper Python boleh memeriksa dataset agregasi; browser QA tetap dijalankan manual melalui Browser tool.

Dataset assertions:

```python
assert checklist_numbers == list(range(1, 83))
assert all(sub["number"].startswith(f'{checklist["number"]}.') for checklist in checklists for sub in checklist["subchecklists"])
assert summary["documents"] == sum(len(sub["files"]) for checklist in checklists for sub in checklist["subchecklists"])
```

- [ ] **Step 2: Jalankan seluruh focused tests**

Run:

```bash
python tools/test_import_ampuh_checklist.py
python tools/test_ampuh_directory_interactions.py
python tools/test_ampuh_directory_e2e.py
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php
```

Expected: semua pass.

- [ ] **Step 3: Verifikasi browser desktop**

Buka `http://127.0.0.1:8080/ampuh` pada 1440×1000. Pastikan:

- hero, pengantar, ringkasan, dan tombol folder utama terlihat;
- 82 checklist terdistribusi di GOBI yang benar;
- semua disclosure tertutup awal;
- membuka GOBI → checklist → sub-checklist memperlihatkan nama file;
- pencarian nama file membuka seluruh ancestor;
- clear search menutup seluruh disclosure;
- filter GOBI beririsan dengan pencarian;
- URL kosong berlabel nonaktif;
- tidak ada error console.

- [ ] **Step 4: Verifikasi browser mobile dan accessibility**

Gunakan viewport CDP 390×844, bukan flag window-size. Uji tema terang/gelap, Tab/Enter/Space, fokus, nama file panjang, no horizontal scroll, target 44px, dan reduced motion.

- [ ] **Step 5: Tambahkan handoff operasional**

Tambahkan bagian `AMPUH 2026` di `HANDOFF.md` yang mencatat:

- route `/ampuh` dan target subdomain `ampuh.pn-natuna.go.id`;
- lokasi dataset dan importer;
- command regenerate persis;
- URL Drive masih kosong sampai diberikan;
- pemasangan URL dilakukan pada dataset/config lalu focused tests dijalankan ulang;
- jangan memasukkan kredensial atau link edit Drive.

- [ ] **Step 6: Jalankan smoke test akhir**

Run:

```bash
curl.exe -I --max-time 15 http://127.0.0.1:8080/ampuh
```

Expected: `HTTP/1.1 200 OK`.

- [ ] **Step 7: Commit task**

```bash
git add tools/test_ampuh_directory_e2e.py HANDOFF.md
git commit -m "test: verify AMPUH directory end to end"
```
