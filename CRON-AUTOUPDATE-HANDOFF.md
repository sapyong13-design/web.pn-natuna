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
menampilkan donut chart % serapan DIPA 01 & 03 + pagu + realisasi.

### Sumber data
- Folder Google Drive (public): `1fVI4UvO54g9u4jdIEjM9EgGGZOS0igNV`
- Naming: `{Laporan Realisasi/LRA} DIPA ... {Bulan} {Tahun}.pdf`
- Latest: Juni 2026

### Cara kerja `tools/refresh-dipa.py`
1. List file folder Gdrive via `embeddedfolderview`.
2. Cari PDF **TERBARU** (prefer "01 dan 03" combined, sort by tahun+bulan).
3. Download → parse text → extract baris **"JUMLAH SELURUHNYA"** per Unit Organisasi (01, 03) → dapat %, pagu, realisasi.
4. Generate donut chart HTML (CSS conic-gradient, 2 ring) + link ke PDF gdrive.
5. Update hanya blok `.dipa-widget` pada module Joomla aktif (DB id 816); module 817 adalah versi lama yang unpublished.

### Menjalankan
```bash
MYSQL_BIN=/path/to/mysql DB_USER=root DB_NAME=pn_natuna_rebuild \
  python tools/refresh-dipa.py
```

### Saat upload PDF baru (mis. Juli 2026)
1. Upload `Laporan Realisasi Anggaran DIPA 01 dan 03 Juli 2026.pdf` ke folder.
2. Jalankan `python tools/refresh-dipa.py`.
3. Donut auto-update ke Juli (%, pagu, link PDF).

### Konfigurasi (`tools/refresh-dipa.py`)
- `FOLDER_ID`, `MODULE_ID` (816), `MYSQL_BIN`/`DB_USER`/`DB_NAME` (env var)

### Catatan parsing
Format PDF: tiap Unit Organisasi (01, 03) punya baris `JUMLAH SELURUHNYA`
dengan kolom realisasi/sisa/%/pagu. Parser cari `%` terdekat sebelum JUMLAH +
angka terbesar setelahnya (pagu). Kalau struktur PDF berubah, parser perlu disesuaikan.

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
