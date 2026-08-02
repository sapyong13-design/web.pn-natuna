# HANDOFF — Cron Auto-Update Feed Instansi (cPanel)

Panduan setup cron job harian untuk auto-refresh berita & pengumuman instansi
(MA RI, Badilum, PT Kepri) sebelum dan sesudah deploy ke cPanel.

---

## Status Tiap Instansi

| Instansi | Sumber | Bisa Auto-Refresh? | Catatan |
|---|---|---|---|
| **Badilum** | `badilum.mahkamahagung.go.id` | ✅ YA — fetch live | Cron harian cukup |
| **PT Kepri** | `pt-kepri.go.id` | ✅ YA — fetch live | Cron harian cukup |
| **MA RI** | `mahkamahagung.go.id` | ⚠️ TIDAK — Cloudflare blokir fetch server-side | Butuh refresh manual/semi-otomatis (lihat bagian MA RI) |

---

## File Penting

| File | Fungsi |
|---|---|
| `/home/USER/private/cron/cron-refresh-instansi.php` | Script CLI-only — WAJIB di luar `public_html` |
| `public_html/templates/pn_natuna_2026/instansi-feed.php` | Logic fetch + parse + fallback + render |
| `public_html/cache/pn_natuna_instansi_feed.json` | Cache hasil fetch; lindungi via `.htaccess` |
| `/home/USER/private/logs/instansi-refresh.log` | Log cron private |

---

## 1. Deploy ke cPanel

### 1a. Tempatkan runtime dan cron secara terpisah
Joomla berada di `public_html`, tetapi cron dan log WAJIB di luar document root:
```
/home/USER/
├── public_html/
│   ├── templates/pn_natuna_2026/instansi-feed.php
│   └── cache/
└── private/
    ├── cron/cron-refresh-instansi.php
    └── logs/
```

Via cPanel File Manager atau SSH:
```bash
mkdir -p /home/USER/public_html/cache /home/USER/private/cron /home/USER/private/logs
chmod 755 /home/USER/public_html/cache
chmod 750 /home/USER/private /home/USER/private/cron /home/USER/private/logs
chmod 640 /home/USER/private/cron/cron-refresh-instansi.php
```
Script menolak semua request non-CLI dengan HTTP 404. Tetap jangan menaruhnya di `public_html`.

> `tools/build-deploy-package.py` sengaja TIDAK memasukkan cron ke ZIP web. Ambil `cron-refresh-instansi.php` dari repository private/source workstation, lalu upload sebagai file terpisah langsung ke `/home/USER/private/cron/`. Jangan menyalinnya melalui `public_html`, bahkan sementara.

### 1c. Cari path PHP CLI
cPanel biasanya punya beberapa versi PHP. Cari path binary PHP:
- Buka **cPanel → Terminal** (atau SSH), jalankan:
  ```bash
  which php
  # atau coba path umum:
  ls /usr/local/bin/php /opt/cpanel/ea-php*/root/usr/bin/php 2>/dev/null
  ```
- Catat path-nya, mis. `/usr/local/bin/php` atau `/opt/cpanel/ea-php82/root/usr/bin/php`.

### 1d. Tes script manual dulu
Via Terminal/SSH:
```bash
PN_NATUNA_JPATH_ROOT=/home/USER/public_html \
PN_NATUNA_LOG_FILE=/home/USER/private/logs/instansi-refresh.log \
php -f /home/USER/private/cron/cron-refresh-instansi.php
```
Output harus menunjukkan proses refresh dan berakhir sukses. Kalau error, cek:
- `PN_NATUNA_JPATH_ROOT` menunjuk root Joomla yang berisi `templates/pn_natuna_2026/instansi-feed.php`.
- PHP versi ≥8.0 dan ekstensi openssl/json/libxml/simplexml/mbstring aktif.
- Cache Joomla writable dan folder private log permission benar.

---

## 2. Setup Cron Job di cPanel

### 2a. Buka Cron Jobs
cPanel → **Cron Jobs** (bagian Advanced).

### 2b. Set jadwal harian
Pilih **Once Per Day** (atau custom). Rekomendasi jam **06:00** pagi (sebelum jam kerja):

| Field | Nilai |
|---|---|
| Minute | `0` |
| Hour | `6` |
| Day | `*` |
| Month | `*` |
| Weekday | `*` |

### 2c. Command cron
```bash
PN_NATUNA_JPATH_ROOT=/home/USER/public_html PN_NATUNA_LOG_FILE=/home/USER/private/logs/instansi-refresh.log PATH_PHP -f /home/USER/private/cron/cron-refresh-instansi.php
```
Ganti `PATH_PHP` dan `USER` sesuai cPanel. Contoh:
```bash
PN_NATUNA_JPATH_ROOT=/home/pnnatuna/public_html PN_NATUNA_LOG_FILE=/home/pnnatuna/private/logs/instansi-refresh.log /usr/local/bin/php -f /home/pnnatuna/private/cron/cron-refresh-instansi.php
```

### 2d. Email notifikasi
cPanel Cron Jobs → **Email** → isi alamat operasional. Jangan membuang stderr sebelum monitoring cron tersedia; script mengembalikan exit code nonzero pada gagal dan detail hanya masuk log/stderr.

---

## 3. Verifikasi Cron Berjalan

### 3a. Cek log private
```bash
tail -n 50 /home/USER/private/logs/instansi-refresh.log
```
Harus ada entry baru sesuai jadwal. Log tidak boleh dapat diakses dari HTTP.

### 3b. Cek cache file
```bash
ls -la public_html/cache/pn_natuna_instansi_feed.json
```
`Modified time` harus update tiap hari.

### 3c. Cek isi cache
```bash
php -r "print_r(json_decode(file_get_contents('public_html/cache/pn_natuna_instansi_feed.json'),true)['pt']['news']);"
```
Harus muncul 5 berita PT Kepri terbaru.

### 3d. Cek di website
Buka homepage, scroll ke section **Berita Instansi**. Badilum & PT Kepri harus
update tiap hari (tanggal & judul berubah).

---

## 4. Refresh MA RI melalui endpoint resmi

Cron mencoba endpoint JSON yang dipakai halaman resmi MA:

- `POST https://www.mahkamahagung.go.id/id/berita` dengan `cat_id=1&page=1&lang=id`
- `POST https://www.mahkamahagung.go.id/id/pengumuman` dengan `cat_id=2&page=1&lang=id`

Browser nyata terverifikasi menerima data resmi terbaru. PHP/cURL lokal menerima Cloudflare challenge; IP keluar cPanel dapat menghasilkan keputusan berbeda. Jalankan cron manual setelah deploy. Status sukses:

```text
berita=live-official-json
pengumuman=live-official-json
```

Jika status `official-cloudflare-challenge`, cron mencoba Google News lalu fallback terkurasi. Jangan replay cookie, memakai CAPTCHA solver, atau menyamarkan bot. Cache hanya diperbarui oleh cron dan homepage selalu membaca cache lokal.

Jadwal produksi cukup sekali sehari, misalnya `15 2 * * *` waktu server. Jika sumber tetap ditolak di cPanel, minta endpoint/whitelist resmi atau gunakan proses browser terjadwal di luar cPanel yang hanya mempromosikan JSON tervalidasi.
---

## 5. Update Sumber Tiap Instansi (kalau URL berubah)

Jika situs sumber ganti struktur/URL, update di
`templates/pn_natuna_2026/instansi-feed.php`, fungsi `pn_natuna_instansi_load()`,
array `$sources`:

```php
$sources = [
    'badilum' => [
        'news'         => ['URL_BERITA',  ['kata_kunci_include'], ['kata_kunci_exclude']],
        'announcements'=> ['URL_PENGUMUMAN', ['kata_kunci_include'], ['kata_kunci_exclude']],
    ],
    'pt' => [
        'news'         => ['URL_BERITA',  ['kata_kunci_include'], ['kata_kunci_exclude']],
        'announcements'=> ['URL_PENGUMUMAN', ['kata_kunci_include'], ['kata_kunci_exclude']],
    ],
];
```

- **include**: kata yang HARUS ada di judul/URL item (filter relevansi).
- **exclude**: kata yang TIDAK boleh ada (buang menu/navigasi/sistem).

Kalau struktur HTML situs sumber berubah total, parser
(`pn_natuna_instansi_parse_items`) mungkin perlu disesuaikan.
Cek dengan tes fetch manual dulu.

---

## 6. Troubleshooting

### Cron tidak jalan
- Cek cPanel Cron Jobs → Email untuk error.
- Cek path PHP CLI (`which php`).
- Tes command lengkap dengan `PN_NATUNA_JPATH_ROOT` dan `PN_NATUNA_LOG_FILE`.
- Pastikan cron file 0640 dan folder private 0750.

### Badilum / PT Kepri tidak update
- Tes fetch manual:
  ```bash
  curl -sI https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html
  ```
  Harus HTTP 200. Kalau 403/503, situs sumber mungkin down atau blokir IP server.
- Cek apakah struktur HTML berubah → parser dapat 0 item → pakai fallback.
- Hapus cache: `rm cache/pn_natuna_instansi_feed.json`, tunggu cron berikutnya.

### MA RI tidak update
- **Expected** — MA diblokir Cloudflare. MA selalu pakai fallback.
- Refresh manual (lihat bagian 4). Atau pakai script Puppeteer semi-otomatis.

### Cache tidak ter-update
- Cek `public_html/cache/` writable (0755, bukan 0777).
- Cek log private `/home/USER/private/logs/instansi-refresh.log`.
- Hapus cache manual, jalankan cron CLI, lalu periksa ulang.

### Feed kosong / 0 item
- Bisa terjadi kalau semua fetch timeout (server sumber lambat).
- Cache tetap diisi dengan fallback (tidak kosong total).
- Cek log untuk error timeout.

---

## 7. Checklist Sebelum Deploy cPanel

- [ ] Cron berada di `/home/USER/private/cron`, BUKAN `public_html`
- [ ] `cron-refresh-instansi.php` diambil terpisah dari repository private, bukan dari ZIP web
- [ ] Log berada di `/home/USER/private/logs`, BUKAN cache publik
- [ ] Request HTTP ke `/cron-refresh-instansi.php` menghasilkan 404
- [ ] Folder cache Joomla writable 0755; folder private 0750; cron file 0640
- [ ] PHP CLI ≥8.0 dengan openssl/json/mbstring/simplexml/libxml
- [ ] Tes command dengan environment root/log sukses
- [ ] Cron harian dibuat dengan path private dan email alert aktif
- [ ] Cache lama dihapus lalu cron pertama berhasil
- [ ] MA RI fallback diperbarui manual bila perlu
- [ ] Homepage diverifikasi setelah cron pertama

---

## 8. Frekuensi Refresh Rekomendasi

| Instansi | Metode | Frekuensi |
|---|---|---|
| Badilum | Cron auto (live fetch) | **Harian** (jam 06:00) |
| PT Kepri | Cron auto (live fetch) | **Harian** (jam 06:00) |
| MA RI | Manual / Puppeteer | **1-2 minggu sekali** (atau saat ada perubahan signifikan) |
| **Survei (SKM+IPAK)** | **Script Python** (`tools/refresh-survey.py`) | **Saat upload PDF baru ke Gdrive** (tiap triwulan) |

---

*Dokumen ini pegangan setup cron auto-update feed instansi di cPanel.
Update kalau ada perubahan struktur situs sumber atau infrastruktur server.*

---

## 9. Auto-Refresh Survei SKM & IPAK (Google Drive → PDF → Image)

Card **Indeks Survei Publik** di sidebar (bawah Role Model) menampilkan hasil
survei terbaru: SKM (Kepuasan Masyarakat) & IPAK (Persepsi Anti Korupsi).

### Sumber data
- Folder Google Drive (public): `SURVEI TERBARU 2026`
- Link: `https://drive.google.com/drive/folders/1XVTZjSGKPzM0XPSTlYg4w7f6Ut-QyG7z`
- Naming file: `{SKM|IPAK} TW{1-4} {TAHUN}.pdf` (mis. `SKM TW2 2026.pdf`)

### Cara kerja script `tools/refresh-survey.py`
1. List file di folder Gdrive via `embeddedfolderview` (tanpa API key, tanpa browser).
2. Cari file **TERBARU** per jenis (SKM, IPAK) — sort by tahun + TW descending.
3. Download PDF → convert ke PNG (PyMuPDF, 200 DPI) → resize web (800px).
4. Update module Joomla (DB id 816) dengan path gambar + tag TW terbaru.

### Menjalankan
```bash
# Prasyarat: pip install PyMuPDF Pillow
cd /path/to/web.pn-natuna
MYSQL_BIN=/path/to/mysql DB_USER=root DB_NAME=pn_natuna_rebuild \
  python tools/refresh-survey.py
```

Output:
```
[1/4] List file dari Google Drive folder...
      Ditemukan 4 file: IPAK TW1/TW2, SKM TW1/TW2
[2/4] SKM: TW2 2026 | IPAK: TW2 2026
[3/4] Download & convert (skip kalau sudah ada)
[4/4] Update module Joomla (DB)
SELESAI.
```

### Saat upload PDF baru (mis. TW3)
1. Upload `SKM TW3 2026.pdf` & `IPAK TW3 2026.pdf` ke folder Gdrive.
2. Jalankan `python tools/refresh-survey.py`.
3. Script auto-detect TW3 sebagai terbaru → download → convert → update card.
4. Hapus cache Joomla jika perlu (`rm cache/*.json`).

### Konfigurasi (di `tools/refresh-survey.py` baris atas)
- `FOLDER_ID` — gdrive folder id (dari URL folder)
- `SURVEY_TYPES` — jenis survei yang di-track (`['SKM', 'IPAK']`)
- `MODULE_ID` — id module Joomla (816)
- `MYSQL_BIN`, `DB_USER`, `DB_PASS`, `DB_NAME` — via env var atau edit langsung

---

## 10. Auto-Refresh Realisasi Anggaran DIPA (Google Drive → PDF → Donut Chart)

Widget **Realisasi Anggaran DIPA** di sidebar (bawah Indeks Pelayanan Publik)
menampilkan donut chart % serapan DIPA 01 & 03 + pagu + realisasi, dengan
**pemilih periode** dan **delta terhadap bulan pembanding**.

### Sumber data
- Folder Google Drive (public): `1fVI4UvO54g9u4jdIEjM9EgGGZOS0igNV`
- Penamaan berkas **tidak konsisten** dan tidak bisa dipercaya. Folder per Juli 2026 berisi `Laporan Realisasi Anggaran DIPA 01 dan 03 Juni 2026.pdf`, `Realisasi DIPA 01 Mei 2026.pdf`, dan `LRA DIPA 01 dan 03 Maret 2026.pdf`. Berkas Mei yang namanya hanya menyebut "DIPA 01" ternyata memuat **kedua** unit organisasi — pagu DIPA 03-nya `178.354.000`, sama persis dengan bulan lain. Jadi isi PDF yang menentukan, bukan namanya.

### Cara kerja `tools/refresh-dipa.py`
1. List file folder Gdrive via `embeddedfolderview`.
2. `collect_periods()` mengambil **semua** bulan yang ada, satu berkas per bulan, terbaru lebih dulu, dibatasi `MAX_PERIODS` (12). Kalau satu bulan punya beberapa berkas, yang bernama `01 dan 03` dimenangkan — bukan karena hanya itu yang lengkap (lihat catatan penamaan di atas), tapi karena namanya paling eksplisit menjanjikan kedua unit.
3. `resolve_periods()` mengunduh + parse tiap PDF. **Hasil parse di-cache per file id** di `cache/pn_natuna_dipa_periods.json` (gitignored) — PDF di Drive tidak pernah berubah isinya, jadi cron berikutnya hanya mengunduh bulan yang benar-benar baru. Satu berkas gagal hanya melewati periode itu, tidak menjatuhkan refresh.
4. `attach_deltas()` menghitung selisih **poin persentase** terhadap periode sebelumnya.
5. `build_html()` merender tab periode + satu panel per bulan; panel teraktif ditandai `is-active` dari server.
6. `update_module_db()` mengganti blok `.dipa-widget` pada modul Joomla aktif (DB id 816); module 817 versi lama yang unpublished. Modul 816 juga memuat skor SKM/IPAK di atas widget, jadi yang diganti hanya potongan dari tag pembuka widget sampai ujung konten.

**Tag pembuka widget adalah kontrak.** `WIDGET_OPEN` ditulis `build_html()` dan dicari `update_module_db()` lewat `WIDGET_OPEN_RE` yang mentoleransi atribut apa pun. Keduanya wajib memakai konstanta itu. Pernah tidak: tag diberi atribut `data-dipa-board` sementara pencocokan masih literal `<div class="dipa-widget">`, sehingga tiap refresh **menambah** satu salinan widget alih-alih menggantinya. Sekarang refresh menolak jalan kalau menemukan lebih dari satu blok, dan memverifikasi hasilnya menyisakan tepat satu. Kontraknya di `tools/test_dipa_periods.py`.

### Delta: poin persentase, dan pembandingnya periode yang tersedia

Serapan itu kumulatif, jadi naik dari 38,20% ke 54,96% adalah **+16,76 poin** — bukan "+43%". Badge memakai satuan `poin` supaya tidak ambigu: `▲` hijau naik, `▼` merah turun, `―` datar. Penurunan tidak disembunyikan; kalau muncul, itu tanda data perlu diperiksa.

**Pembandingnya periode yang benar-benar ada di folder, bukan bulan kalender.** Kalau Mei tidak diunggah, pembanding Juni adalah April, dan itu dinyatakan di `title` badge (`Selisih +16,76 poin dibanding April 2026`). Periode paling awal menampilkan *"periode awal"*, bukan badge kosong.

### Pemilih periode

Tablist dengan label pendek (`Jun 26`, `Mei 26`, …). Perilaku keyboard (←/→/Home/End) dan strukturnya identik dengan tab Kabar Instansi — `setupDipaPeriods()` di `template.js` sengaja mencerminkan `setupInstansiTabs()` supaya hanya ada satu cara tab bekerja di situs ini. Panel aktif sudah benar dari server, jadi **tanpa JS angkanya tetap tampil**, hanya tidak bisa berganti bulan. Kontrak: `tools/test_dipa_periods.py`.

### Menjalankan
```bash
MYSQL_BIN=/path/to/mysql DB_USER=root DB_NAME=pn_natuna_rebuild \
  python tools/refresh-dipa.py
```

### Saat upload PDF baru (mis. Juli 2026)
1. Upload `Laporan Realisasi Anggaran DIPA 01 dan 03 Juli 2026.pdf` ke folder.
2. Jalankan `python tools/refresh-dipa.py`.
3. Juli masuk sebagai tab baru dan jadi periode aktif; bulan lama tetap bisa dipilih dan tidak diunduh ulang.

### Konfigurasi (`tools/refresh-dipa.py`)
- `FOLDER_ID`, `MODULE_ID` (816), `MAX_PERIODS` (12), `CACHE_PATH`
- `MYSQL_BIN`/`MYSQL_DEFAULTS_FILE`/`DB_USER`/`DB_PASS`/`DB_NAME` (env var)

### Catatan parsing
Format PDF: tiap Unit Organisasi (01, 03) punya baris `JUMLAH SELURUHNYA`
dengan kolom realisasi/sisa/%/pagu. Parser cari `%` terdekat sebelum JUMLAH +
angka terbesar setelahnya (pagu). Kalau struktur PDF berubah, parser perlu disesuaikan.
Menghapus `cache/pn_natuna_dipa_periods.json` memaksa parse ulang seluruh folder — lakukan itu bila parser diperbaiki, karena cache menyimpan hasil parser lama.

## Deploy Staging Commit `149f88e`

Gunakan prosedur ini saat diminta meng-upgrade `new.pn-natuna.go.id`. Target hanya staging; jangan menyentuh document root domain utama.

### 1. Muat env dan verifikasi marker staging

```bash
set -a
. /home/pnnatuna/private/cron/pn-natuna.env
set +a
printf 'SOURCE=%s\nWEBROOT=%s\nDB=%s\n' "$PN_NATUNA_SOURCE_ROOT" "$PN_NATUNA_JPATH_ROOT" "$DB_NAME"
test -f "$PN_NATUNA_JPATH_ROOT/.pn-natuna-staging" || { echo 'STOP: marker staging hilang'; exit 1; }
test "$(cat "$PN_NATUNA_JPATH_ROOT/.pn-natuna-staging")" = 'new.pn-natuna.go.id' || { echo 'STOP: marker staging salah'; exit 1; }
```

### 2. Pull commit target

```bash
cd "$PN_NATUNA_SOURCE_ROOT"
git status --short --branch
git pull --ff-only origin continue-joomla-rebuild-polish
git log -1 --oneline
```

Target minimal: `149f88e feat: polish court information experiences`. Checkout harus bersih sebelum pull.

### 3. Backup DB wajib

```bash
DB_BACKUP_DIR="/home/pnnatuna/private/backups/db-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$DB_BACKUP_DIR" && chmod 700 "$DB_BACKUP_DIR"
MYSQLDUMP_BIN="$(command -v mariadb-dump || command -v mysqldump)"
"$MYSQLDUMP_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" --single-transaction --quick --skip-lock-tables --no-tablespaces "$DB_NAME" > "$DB_BACKUP_DIR/pre-149f88e.sql"
chmod 600 "$DB_BACKUP_DIR/pre-149f88e.sql"
test -s "$DB_BACKUP_DIR/pre-149f88e.sql" && echo "Database backup: OK — $DB_BACKUP_DIR/pre-149f88e.sql"
```

Jangan lanjut bila backup tidak menampilkan `Database backup: OK`.

### 4. Sinkronkan code ke webroot staging

```bash
cd "$PN_NATUNA_SOURCE_ROOT"
"$PYTHON_BIN" tools/deploy-cpanel.py \
  --target "$PN_NATUNA_JPATH_ROOT" \
  --branch continue-joomla-rebuild-polish \
  --url https://new.pn-natuna.go.id \
  --no-pull
```

Target: `Staging deployed: commit 149f88e` atau commit sesudahnya.

### 5. Terapkan migrasi normal

```bash
"$PYTHON_BIN" tools/apply-db-migrations.py \
  --mysql "$MYSQL_BIN" \
  --mysql-defaults-file "$MYSQL_DEFAULTS_FILE" \
  --database "$DB_NAME"
```

Jangan gunakan `--reapply`. Registry akhir minimal harus memuat `20260821_repair_transparency_document_links.sql`. Migrasi percobaan `20260815` dan `20260816` tidak boleh ada.

### 6. Validasi menu, file, cache, dan updater

```bash
"$MYSQL_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" -N -B "$DB_NAME" -e "SELECT COUNT(*) total_items,SUM(lft>=rgt) invalid_bounds,COUNT(*)-COUNT(DISTINCT lft) duplicate_lft,COUNT(*)-COUNT(DISTINCT rgt) duplicate_rgt FROM pnn_menu WHERE client_id=0;"
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/css/template.css" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/css/template.css" && echo 'CSS: OK'
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/js/template.js" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/js/template.js" && echo 'JavaScript: OK'
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/sipp-schedule.php" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/sipp-schedule.php" && echo 'SIPP renderer: OK'
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/html/mod_custom/default.php" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/html/mod_custom/default.php" && echo 'Brand override: OK'
cd "$PN_NATUNA_JPATH_ROOT"
"$PHP_BIN" cli/joomla.php cache:clean
/bin/sh "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-all.sh"
```

Nilai `invalid_bounds`, `duplicate_lft`, dan `duplicate_rgt` harus `0`.

### 7. Smoke test

```bash
for path in / /profil-pengadilan /berita-dan-pengumuman /informasi-perkara /zona-integritas/agen-perubahan /zona-integritas/role-model /transparansi/ringkasan-lkjip /transparansi/laporan-survei-harian; do
  code="$(curl -L -s -o /dev/null -w '%{http_code}' "https://new.pn-natuna.go.id$path")"
  printf '%s %s\n' "$code" "$path"
done
```

Semua harus HTTP `200`. Larangan tetap: jangan `--full-staging`, `--reset-database`, `--reapply`, mengubah Python App, atau deploy ke domain utama.

---

## Deploy Staging Commit `2043f49` (2 Agu 2026)

Ikuti seluruh langkah **Deploy Staging** di atas, dengan tiga tambahan yang khusus untuk batch ini. Target tetap hanya `https://new.pn-natuna.go.id`.

### Yang berubah di batch ini

Perombakan permukaan berita (kop berlambang, indeks arsip tahun, kartu satu tautan, judul rata dengan foto), halaman galat `error.php` yang baru, dan **perbaikan pencarian situs yang selama ini mati**. Empat migrasi baru: `20260901`, `20260902`, `20260903`, `20260904`.

### Tambahan 1 — berkas templat baru wajib ikut tersalin

`git pull` pada private checkout tidak memperbarui webroot. Selain `template.css`, `js/template.js`, dan `html/com_content/article/default.php` yang sudah disebut prosedur lama, batch ini menambahkan berkas yang **belum pernah ada di webroot**:

```bash
for f in templates/pn_natuna_2026/error.php \
         templates/pn_natuna_2026/html/com_finder/search/default.php \
         templates/pn_natuna_2026/html/com_finder/search/default_form.php \
         templates/pn_natuna_2026/html/com_finder/search/default_results.php \
         templates/pn_natuna_2026/html/com_finder/search/default_result.php \
         templates/pn_natuna_2026/html/com_content/category/blog.php \
         templates/pn_natuna_2026/html/com_content/category/blog_item.php \
         templates/pn_natuna_2026/templateDetails.xml; do
  cmp "$PN_NATUNA_SOURCE_ROOT/$f" "$PN_NATUNA_JPATH_ROOT/$f" >/dev/null 2>&1 && echo "OK   $f" || echo "BEDA $f"
done
```

Setiap baris `BEDA` wajib disalin dari private checkout ke webroot sebelum cache dibersihkan.

### Tambahan 2 — indeks Smart Search wajib dibangun di staging

Isi indeks pencarian **tidak ikut Git**; ia hidup di basis data masing-masing lingkungan. Tanpa langkah ini rute `/cari` berdiri tetapi tidak pernah menemukan apa pun.

```bash
cd "$PN_NATUNA_JPATH_ROOT"
"$PHP_BIN" -d memory_limit=1024M cli/joomla.php finder:index
```

Verifikasi jumlahnya wajar dibanding jumlah artikel terbit:

```bash
"$MYSQL_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" -N -B "$DB_NAME" \
  -e "SELECT (SELECT COUNT(*) FROM pnn_finder_links) AS indexed, (SELECT COUNT(*) FROM pnn_content WHERE state=1) AS published;"
```

`indexed` harus mendekati atau melebihi `published`. Bila hanya belasan, indeksnya gagal - jangan lanjut.

### Tambahan 3 — smoke test khusus batch ini

```bash
printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' 'https://new.pn-natuna.go.id/cari?q=posbakum')" '/cari?q=posbakum'
printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' 'https://new.pn-natuna.go.id/berita/legacy-pengumuman-seleksi-posbakum-tahun-2026')" 'redirect duplikat (harus 301)'
printf '%s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' 'https://new.pn-natuna.go.id/berita/halaman-yang-tidak-ada')" 'halaman galat (harus 404)'
curl -s 'https://new.pn-natuna.go.id/cari?q=posbakum' | grep -c 'hasil untuk'
curl -s 'https://new.pn-natuna.go.id/berita/halaman-yang-tidak-ada' | grep -c 'Halaman tidak ditemukan'
```

Harapan: `/cari` **200**, tautan duplikat **301**, halaman tidak dikenal **404**, dan kedua `grep -c` mengembalikan **1**. Bila halaman galat masih berbahasa Inggris, `error.php` belum tersalin ke webroot.

### Wajib diperiksa sebelum mulai

```bash
cd "$PN_NATUNA_SOURCE_ROOT"
git fetch origin continue-joomla-rebuild-polish
git rev-parse --short HEAD
git rev-parse --short origin/continue-joomla-rebuild-polish
git status --short
```

Pohon kerja wajib bersih dan HEAD wajib sama dengan `origin/continue-joomla-rebuild-polish` sesudah pull. Larangan lama tetap berlaku: jangan `--reapply`, `--full-staging`, `--reset-database`, atau mengarah ke document root domain utama.


## Deploy Staging Commit `3587f711` (2 Agu 2026) — batch aktif

Ini target deploy sekarang. Ikuti **Deploy Staging Commit `149f88e`** sebagai prosedur induk, lalu **seluruh tambahan `2043f49`**, lalu tambahan di bawah ini.

### Kenapa dua batch sekaligus

`2043f49` **belum pernah turun ke staging**. Diperiksa 2 Agu 2026: `https://new.pn-natuna.go.id/cari?q=posbakum` membalas **404** dan kotak pencarian beranda masih menembak `com_search`. Jadi staging melompati dua batch, dan **enam migrasi** ikut sekaligus: `20260901`–`20260904` dari batch lalu, `20260905`–`20260906` dari batch ini. Jalankan `apply-db-migrations.py` sekali; ia memutar semuanya berurutan.

### Yang berubah di batch ini

Pencarian situs diarahkan ke tujuan yang benar-benar dicari warga: blok **Sistem resmi** (SIPP, e-Court, e-Berpadu, SIWAS, Direktori Putusan, Eksekusi Badilum) dan **kartu jalur perkara** untuk kueri berbentuk nomor perkara; kotak pencarian beranda yang mati diperbaiki lewat migrasi; letaknya di ponsel dipindah dari 91% ke 13% tinggi halaman; dan pencatatan statistik pencarian dinyalakan.

### Tambahan 1 — dua berkas templat baru

`deploy-cpanel.py` memakai `mirror_code_dir`, jadi berkas baru ikut tersalin sendiri dan tidak perlu disalin manual. Yang wajib hanya **memastikan** keduanya mendarat — `sistem-daring.json` adalah tipe berkas pertama di `templates/.../data/`, dan bila ia tidak ada, blok Sistem resmi mati diam-diam tanpa galat:

```bash
for f in templates/pn_natuna_2026/data/sistem-daring.json \
         templates/pn_natuna_2026/html/com_finder/search/default_caseroute.php; do
  cmp "$PN_NATUNA_SOURCE_ROOT/$f" "$PN_NATUNA_JPATH_ROOT/$f" >/dev/null 2>&1 && echo "OK   $f" || echo "BEDA $f"
done
```

### Tambahan 2 — `finder:index` tetap wajib

Sama seperti batch lalu dan bukan pengulangan sia-sia: isi indeks hidup di basis data tiap lingkungan dan **tidak ikut Git**. Tanpa ini `/cari` berdiri tetapi kosong, dan blok Sistem resmi tidak punya hasil untuk didampingi.

```bash
cd "$PN_NATUNA_JPATH_ROOT"
"$PHP_BIN" -d memory_limit=1024M cli/joomla.php finder:index
```

### Tambahan 3 — smoke test khusus batch ini

```bash
S='https://new.pn-natuna.go.id'
printf '%s /cari?q=posbakum\n'      "$(curl -s -o /dev/null -w '%{http_code}' "$S/cari?q=posbakum")"
echo "hasil posbakum : $(curl -s "$S/cari?q=posbakum" | grep -c 'site-search__item')"
echo "blok sistem    : $(curl -s "$S/cari?q=salinan+putusan" | grep -c 'site-search__case-route')"
echo "kartu perkara  : $(curl -s "$S/cari?q=12/Pdt.G/2026/PN+Ntn" | grep -c 'site-search__case-route')"
echo "blok TIDAK muncul di kueri informasional (harus 0):"
for q in biaya+perkara ptsp mediasi prodeo kontak zona+integritas posbakum; do
  printf '  %s %s\n' "$(curl -s "$S/cari?q=$q" | grep -c 'site-search__case-route')" "$q"
done
echo "kotak beranda  : $(curl -s "$S/" | grep -c 'com_search')  (harus 0)"
```

Harapan: `/cari` **200**, `posbakum` mengembalikan hasil (>0), `salinan putusan` dan nomor perkara memunculkan blok (masing-masing >0), ketujuh kueri informasional **0** — kalau ada yang bukan 0, blok menutupi hasil yang sudah tepat — dan `com_search` **0** di beranda. Bila `com_search` masih 1, migrasi `20260906` belum jalan atau cache belum dibersihkan.

### Tambahan 4 — statistik pencarian

Migrasi `20260905` menyalakan `gather_search_statistics`. Verifikasi setelah beberapa kueri smoke test di atas:

```bash
"$MYSQL_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" -N -B "$DB_NAME" \
  -e "SELECT COUNT(*) FROM pnn_finder_logging;"
```

Harus lebih dari `0`. Isinya tidak boleh ditampilkan di situs publik: istilah pencarian di pengadilan bisa memuat nama pihak dan nomor perkara. Bacanya lewat SQL langsung, dan pandangan paling berguna adalah `WHERE results = 0` — daftar persis apa yang dicari warga dan tidak ditemukan situs.

### Yang TIDAK ada di batch ini

Penghitung kunjungan di kaki situs sempat dibuat lalu **dibalik** atas permintaan pemilik (`2c04a1b2` lalu `3587f711`). Migrasi `20260907` ikut hilang. Bila staging pernah terlanjur menerimanya, kolomnya dibuang dan catatan ledger-nya dihapus:

```bash
"$MYSQL_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" "$DB_NAME" -e "
  ALTER TABLE pnn_visitor_totals DROP COLUMN counting_since;
  DELETE FROM pnn_project_migrations WHERE name LIKE '20260907%';"
```

Lewati bila `20260907` tidak pernah tercatat di staging — kondisi normal, karena batch ini belum pernah turun.

## Hardening eksternal menuju baseline 8/10

Audit aktif-terbatas 2 Agu 2026 menemukan `new.pn-natuna.go.id` masih melayani HTTP **200**, membocorkan `X-Powered-By: PHP/8.4.23`, cookie Joomla tidak memperlihatkan `SameSite`, dan `/administrator/` terbuka langsung. Commit hardening berikutnya menutup bagian yang aman dikendalikan repository: redirect HTTPS yang proxy-aware, HSTS awal 300 detik, Permissions-Policy, penghapusan header versi PHP, dan kontrak agar `.htaccess`/`htaccess.txt` tidak menyimpang. CSP belum dipaksakan: YouTube, Instagram, Maps, dan Joomla admin harus diinvetarisasi dalam Report-Only dahulu.

Setelah deploy code normal, lakukan empat kontrol manual cPanel:

1. **MultiPHP INI Editor** untuk domain staging: `expose_php = Off`; jangan mengandalkan `Header unset` saja karena host dapat menambahkan header setelah aturan `.htaccess`.
2. **Joomla Global Configuration**: Force HTTPS = Entire Site. Redirect `.htaccess` tetap pertahanan pertama agar Joomla tidak membuat sesi HTTP.
3. **cPanel Directory Privacy** pada `/home/pnnatuna/new.pn-natuna.go.id/administrator`; buat kredensial outer gate berbeda dari akun Joomla. Uji login, MFA, Media Manager/AJAX, logout, dan jalur recovery sebelum menutup sesi operator yang masih aktif.
4. **Cookie SameSite**: gunakan opsi Joomla/provider yang didukung untuk `SameSite=Lax`; jangan menyunting core Joomla. Verifikasi lewat `Set-Cookie`, bukan hanya nilai panel.

Verifikasi dari Terminal cPanel:

```bash
echo 'HTTP harus 301 ke HTTPS:'
curl -sI http://new.pn-natuna.go.id/ | grep -iE 'HTTP/|Location:'
echo 'Header HTTPS:'
curl -sI https://new.pn-natuna.go.id/ | grep -iE 'HTTP/|Strict-Transport|Permissions-Policy|X-Content-Type|X-Powered-By|X-Robots-Tag'
echo 'Admin harus meminta outer gate (401) sebelum Joomla:'
curl -sI https://new.pn-natuna.go.id/administrator/ | grep -iE 'HTTP/|WWW-Authenticate:'
echo 'Cookie harus Secure, HttpOnly, SameSite=Lax:'
curl -sI https://new.pn-natuna.go.id/ | grep -i '^set-cookie:'
```

Harapan: HTTP `301`; HTTPS memuat HSTS `max-age=300`, Permissions-Policy, nosniff, dan noindex staging; **tidak ada** X-Powered-By; admin `401` + `WWW-Authenticate`; cookie memuat `Secure`, `HttpOnly`, dan `SameSite=Lax`. Jangan naikkan HSTS atau menambah `includeSubDomains`/`preload` sebelum seluruh subdomain dan rollback terbukti.

## Resume Deployment Batch `32b7274` / `45423b0`

Status staging terakhir pada 21 Juli 2026:

- File template batch harus berada di webroot: `css/template.css`, `js/template.js`, dan `html/com_content/article/default.php`.
- Migrasi `20260803_align_transparency_document_cards.sql` sudah diterapkan dan tercatat.
- Migrasi `20260804` sampai `20260810` belum diterapkan.
- Percobaan pertama `20260804` gagal karena collation session berbeda dari `pnn_content.introtext`.
- Commit `45423b0` memperbaiki runner agar membaca `CHARACTER_SET_NAME` dan `COLLATION_NAME` langsung dari kolom tersebut.

### Langkah pertama saat melanjutkan

```bash
set -a
. /home/pnnatuna/private/cron/pn-natuna.env
set +a
cd "$PN_NATUNA_SOURCE_ROOT"
git pull --ff-only origin continue-joomla-rebuild-polish
git log -1 --oneline
```

HEAD harus `45423b0` atau commit sesudahnya.

### Backup wajib sebelum retry

Migrasi `20260810` membangun ulang seluruh nested-set menu (`lft/rgt/level`). Pastikan dump privat tidak kosong:

```bash
DB_BACKUP_DIR="/home/pnnatuna/private/backups/db-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$DB_BACKUP_DIR"
chmod 700 "$DB_BACKUP_DIR"
MYSQLDUMP_BIN="$(command -v mariadb-dump || command -v mysqldump)"
"$MYSQLDUMP_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" --single-transaction --quick --skip-lock-tables "$DB_NAME" pnn_menu pnn_content pnn_modules pnn_project_migrations > "$DB_BACKUP_DIR/pre-32b7274.sql"
test -s "$DB_BACKUP_DIR/pre-32b7274.sql" && echo "Database backup: OK"
chmod 600 "$DB_BACKUP_DIR/pre-32b7274.sql"
```

Jangan lanjut jika `Database backup: OK` tidak muncul.

### Retry migrasi

```bash
"$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/apply-db-migrations.py" \
  --mysql "$MYSQL_BIN" \
  --mysql-defaults-file "$MYSQL_DEFAULTS_FILE" \
  --database "$DB_NAME"
```

Target: `20260803` di-skip; `20260804` sampai `20260810` di-apply. Jangan gunakan `--reapply`.

### Validasi menu dan finalisasi

```bash
"$MYSQL_BIN" --defaults-extra-file="$MYSQL_DEFAULTS_FILE" -N -B "$DB_NAME" -e "SELECT COUNT(*) total_items,SUM(lft>=rgt) invalid_bounds,COUNT(*)-COUNT(DISTINCT lft) duplicate_lft,COUNT(*)-COUNT(DISTINCT rgt) duplicate_rgt FROM pnn_menu WHERE client_id=0;"
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/css/template.css" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/css/template.css" && echo "CSS: OK"
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/js/template.js" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/js/template.js" && echo "JavaScript: OK"
cmp "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026/html/com_content/article/default.php" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026/html/com_content/article/default.php" && echo "Renderer: OK"
cd "$PN_NATUNA_JPATH_ROOT"
"$PHP_BIN" cli/joomla.php cache:clean
/bin/sh "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-all.sh"
```

Nilai `invalid_bounds`, `duplicate_lft`, dan `duplicate_rgt` harus `0`. Jika `cmp` gagal, salin file terkait dari private checkout ke webroot sebelum membersihkan cache.

---

## Status cPanel Aktual — `new.pn-natuna.go.id`

Setup ini sudah dibuktikan berhasil pada akun cPanel `pnnatuna`:

- Private checkout: `/home/pnnatuna/repos/web.pn-natuna`
- Konfigurasi runtime: `/home/pnnatuna/private/cron/pn-natuna.env`
- Kredensial MySQL privat: `/home/pnnatuna/private/cron/mysql.cnf` (mode `0600`; jangan commit atau salin ke chat)
- Log gabungan: `/home/pnnatuna/private/logs/cron-refresh-all.log`
- Python virtual environment: `/home/pnnatuna/virtualenv/private/python/pn-natuna-cron/3.12/bin/python`
- Python 3.12 dengan `PyMuPDF` dan `Pillow` sudah terverifikasi melalui `Cron Python: OK`.
- Runner berhasil dijalankan melalui `/bin/sh`; bentuk ini dipakai karena eksekusi langsung pernah menghasilkan `Permission denied` pada jailshell.
- Nilai document root staging dan nama database tetap berasal dari `pn-natuna.env`; jangan menduplikasi kredensial di repository.

### Refresh manual semua sumber

Jalankan command ini dari cPanel Terminal:

```bash
set -a; . /home/pnnatuna/private/cron/pn-natuna.env; set +a; /bin/sh "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-all.sh"
```

Command memuat konfigurasi privat lalu memperbarui instansi, YouTube, SIPP, survei SKM/IPAK, dan DIPA secara berurutan. Target akhir: `instansi berhasil`, `youtube berhasil`, `sipp berhasil`, `survei berhasil`, dan `dipa berhasil`.

### Refresh manual per sumber

Muat environment sekali per sesi Terminal:

```bash
set -a
. /home/pnnatuna/private/cron/pn-natuna.env
set +a
```

Kemudian pilih:

```bash
"$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/cron-refresh-instansi.php"
"$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-youtube.php"
"$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-sipp.php"
"$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-survey.py"
"$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-dipa.py"
```

### Pemeriksaan cepat

```bash
tail -n 100 /home/pnnatuna/private/logs/cron-refresh-all.log
```

PDF Google Drive tidak memicu website secara instan. Dokumen terbaca saat cron atau refresh manual berikutnya. Nama survei wajib mengikuti `SKM TW{1-4} {TAHUN}.pdf` dan `IPAK TW{1-4} {TAHUN}.pdf`; nama DIPA yang aman: `Laporan Realisasi Anggaran DIPA 01 dan 03 {Bulan} {Tahun}.pdf`. Folder Drive wajib publik untuk siapa pun yang memiliki link.

---

## Setup Ringkas Semua Updater

Semua updater kini dapat dijalankan dari **private checkout** repository dengan satu runner. Direktori `tools/` sengaja **tidak masuk ZIP deployment** dan tidak perlu ditaruh di `public_html`.

### 1. Siapkan file privat

```bash
mkdir -p /home/USER/private/cron /home/USER/private/logs
cp /home/USER/repos/web.pn-natuna/tools/cron-cpanel.env.example /home/USER/private/cron/pn-natuna.env
cp /home/USER/repos/web.pn-natuna/tools/mysql.cnf.example /home/USER/private/cron/mysql.cnf
chmod 700 /home/USER/private/cron
chmod 600 /home/USER/private/cron/pn-natuna.env /home/USER/private/cron/mysql.cnf
chmod 700 /home/USER/repos/web.pn-natuna/tools/cron-refresh-all.sh
```

Edit `pn-natuna.env`: ganti `USER`, path PHP/Python/MySQL, dan `DB_NAME`. Edit `mysql.cnf`: isi user dan password database cPanel. Password hanya berada di file mode `0600`, bukan command Cron Jobs.

Pasang dependensi Python sekali:

```bash
/usr/bin/python3 -m pip install --user PyMuPDF Pillow
```

### 2. Tes manual

```bash
set -a
. /home/USER/private/cron/pn-natuna.env
set +a
/home/USER/repos/web.pn-natuna/tools/cron-refresh-all.sh
```

Runner melanjutkan updater lain bila satu sumber gagal, lalu mengembalikan exit nonzero agar email cron memberi peringatan. Root web selalu berasal dari `PN_NATUNA_JPATH_ROOT`; cache masuk `public_html/cache`, gambar survei masuk `public_html/images/surveys`, dan log masuk folder privat.

### 3. Satu command cPanel Cron Jobs

Jadwal yang sederhana: setiap hari pukul 06.00.

```bash
0 6 * * * set -a; . /home/USER/private/cron/pn-natuna.env; set +a; /home/USER/repos/web.pn-natuna/tools/cron-refresh-all.sh >> /home/USER/private/logs/cron-refresh-all.log 2>&1
```

Untuk YouTube lebih cepat, runner yang sama boleh dijalankan per jam, tetapi juga akan memeriksa sumber lain. Jangan menjalankan `refresh-survey.py` dan `refresh-dipa.py` bersamaan; keduanya menyunting modul Joomla `816` secara berurutan di runner ini.

### 4. Verifikasi

```bash
tail -n 100 /home/USER/private/logs/cron-refresh-all.log
test -s /home/USER/public_html/cache/pn_natuna_instansi_feed.json
test -s /home/USER/public_html/cache/pn_natuna_youtube/feed.json
test -s /home/USER/public_html/cache/pn_natuna_sipp_schedule.json
```

Homepage harus menampilkan feed instansi terbaru, lima video YouTube, jadwal SIPP, periode survei terbaru yang tersedia, dan periode DIPA terbaru yang tersedia.

---

---

## 9. Refresh YouTube per Jam

Cache YouTube dibuat oleh script CLI-only `tools/cron-refresh-youtube.php`. Script mengambil Atom channel `UCuPb35OggK2PKdW7Ed0qszA` melalui HTTPS, mempertahankan cache lama jika fetch, parse, atau promosi gagal, dan memakai `PN_NATUNA_JPATH_ROOT` untuk menulis cache ke Joomla root. Gunakan runner tunggal pada bagian **Setup Ringkas Semua Updater**; script tidak perlu disalin ke `public_html`.
