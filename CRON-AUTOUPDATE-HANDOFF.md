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
| `cron-refresh-instansi.php` | Script cron — hapus cache lama, re-fetch, tulis cache baru |
| `templates/pn_natuna_2026/instansi-feed.php` | Logic fetch + parse + fallback + render |
| `cache/pn_natuna_instansi_feed.json` | Cache hasil fetch (TTL 1 jam di runtime) |
| `cache/instansi-refresh.log` | Log cron (append tiap run) |

---

## 1. Deploy ke cPanel

### 1a. Upload file
Upload repo ke cPanel (`public_html/` atau subfolder). Pastikan ada:
```
public_html/
├── cron-refresh-instansi.php
├── templates/pn_natuna_2026/instansi-feed.php
├── cache/                          ← folder ini HARUS ada & writable
│   └── (kosong, cron akan isi)
```

### 1b. Pastikan folder cache writable
Via cPanel File Manager atau SSH:
```bash
mkdir -p public_html/cache
chmod 755 public_html/cache
```

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
php -f /home/USER/public_html/cron-refresh-instansi.php
```
Output harusnya:
```
[2026-07-04 06:00:00] Mulai refresh instansi feed...
  - Mahkamah Agung RI: 5 berita, 5 pengumuman
  - Badilum: 5 berita, 5 pengumuman
  - PT Kepri: 5 berita, 5 pengumuman
[2026-07-04 06:00:00] SELESAI. Cache ditulis: .../cache/pn_natuna_instansi_feed.json
```
Kalau error, cek:
- Path `JPATH_ROOT` benar (script pakai `dirname(__FILE__)` = folder script berada).
- PHP versi ≥ 8.0 (butuh `str_contains`, arrow functions, named args).
- Ekstensi `openssl`, `json`, `libxml`, `simplexml`, `mbstring` aktif.

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
```
PATH_PHP -f /home/USER/public_html/cron-refresh-instansi.php
```
Ganti:
- `PATH_PHP` → path PHP CLI dari langkah 1c (mis. `/usr/local/bin/php`)
- `USER` → username cPanel
- `public_html` → path folder Joomla

Contoh lengkap:
```
/usr/local/bin/php -f /home/pnnatuna/public_html/cron-refresh-instansi.php
```

### 2d. Email notifikasi
cPanel Cron Jobs → **Email** → isi email untuk terima output cron.
Setiap run kirim log (berhasil/error). Kalau tidak mau email, tambahkan
`> /dev/null 2>&1` di akhir command (tapi log tetap masuk
`cache/instansi-refresh.log`).

---

## 3. Verifikasi Cron Berjalan

### 3a. Cek log
```bash
cat public_html/cache/instansi-refresh.log
```
Harus ada entry baru tiap hari dengan timestamp jam 06:00.

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

## 4. Refresh MA RI (Cloudflare — TIDAK bisa auto via cron)

MA RI (`mahkamahagung.go.id`) diblokir Cloudflare untuk fetch server-side
(PHP/curl dapat halaman challenge, bukan konten). Cron hanya bisa refresh
Badilum & PT. **MA RI butuh refresh manual berkala** (mis. 1-2 minggu sekali).

### Cara manual refresh MA RI (paling reliable):

1. **Buka halaman MA di browser** (browser REAL bisa lewati Cloudflare):
   - Berita: `https://mahkamahagung.go.id/id/berita`
   - Pengumuman: `https://mahkamahagung.go.id/id/pengumuman`

2. **Catat 5 judul + tanggal terbaru** dari tiap halaman.

3. **Update fallback di `templates/pn_natuna_2026/instansi-feed.php`**,
   array `'ma' => ['news' => [...], 'announcements' => [...]]`.
   Format per item:
   ```php
   ['date' => '1 Jul', 'title' => 'JUDUL ASLI', 'url' => 'https://mahkamahagung.go.id/id/berita/7330/slug'],
   ```
   URL harus link langsung ke artikel (dari `<a href>` di halaman MA),
   bukan halaman listing.

4. **Hapus cache**:
   ```bash
   rm public_html/cache/pn_natuna_instansi_feed.json
   ```
   Atau tunggu cron harian berikutnya (yang akan re-fetch + pakai fallback baru).

5. **Commit & deploy** perubahan `instansi-feed.php`.

### Semi-otomatis MA RI (opsional, butuh Node.js + Puppeteer):

Kalau server lokal/dev punya Node.js, bisa pakai script scraper headless browser.
Install:
```bash
npm install puppeteer
```

Script `tools/scrape-ma-feed.js`:
```javascript
const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch({ headless: 'new' });
  const page = await browser.newPage();
  await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

  async function grab(url) {
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
    await new Promise(r => setTimeout(r, 3000)); // tunggu Cloudflare challenge selesai
    return await page.evaluate(() => {
      const mon = {Januari:'Jan',Februari:'Feb',Maret:'Mar',April:'Apr',Mei:'Mei',Juni:'Jun',Juli:'Jul',Agustus:'Agu',September:'Sep',Oktober:'Okt',November:'Nov',Desember:'Des'};
      return Array.from(document.querySelectorAll('div.list')).map(l => {
        const title = (l.querySelector('h1,h2,h3')||l.querySelector('a'))?.textContent?.trim() || '';
        const content = l.querySelector('.content')?.textContent || '';
        const dm = content.match(/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(20\d{2})/);
        const url = l.querySelector('a[href]')?.href || '';
        return { date: dm ? dm[1]+' '+mon[dm[2]] : '', title, url };
      }).filter(x => x.title.length >= 15).slice(0, 5);
    });
  }

  const news = await grab('https://mahkamahagung.go.id/id/berita');
  const announcements = await grab('https://mahkamahagung.go.id/id/pengumuman');
  console.log(JSON.stringify({ news, announcements }, null, 2));
  await browser.close();
})();
```

Jalankan:
```bash
node tools/scrape-ma-feed.js
```
Output JSON → copy ke array `'ma'` di `instansi-feed.php`, commit, deploy.

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
- Cek **cPanel → Cron Jobs → Email** untuk error.
- Cek path PHP CLI benar (`which php`).
- Tes command manual via Terminal: `php -f /path/cron-refresh-instansi.php`.
- Cek permission: `chmod 644 cron-refresh-instansi.php`.

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
- Cek `cache/` folder writable: `chmod 755 cache`.
- Cek `cache/instansi-refresh.log` untuk error.
- Hapus cache manual, tunggu cron, cek lagi.

### Feed kosong / 0 item
- Bisa terjadi kalau semua fetch timeout (server sumber lambat).
- Cache tetap diisi dengan fallback (tidak kosong total).
- Cek log untuk error timeout.

---

## 7. Checklist Sebelum Deploy cPanel

- [ ] `cron-refresh-instansi.php` ter-upload ke root Joomla
- [ ] Folder `cache/` ada & writable (chmod 755)
- [ ] PHP CLI ≥ 8.0 dengan ekstensi: openssl, json, mbstring, simplexml, libxml
- [ ] Tes `php -f cron-refresh-instansi.php` sukses via Terminal
- [ ] Cron job dibuat (daily, jam 06:00) dengan path PHP benar
- [ ] Email notifikasi cron di-set
- [ ] Hapus `cache/pn_natuna_instansi_feed.json` setelah deploy (force refresh pertama)
- [ ] MA RI fallback sudah ter-update dengan judul terbaru (refresh manual)
- [ ] Cek homepage setelah cron pertama jalan: Badilum & PT update, MA tampil fallback

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
5. Update module Joomla (DB id 817).

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
- `FOLDER_ID`, `MODULE_ID` (817), `MYSQL_BIN`/`DB_USER`/`DB_NAME` (env var)

### Catatan parsing
Format PDF: tiap Unit Organisasi (01, 03) punya baris `JUMLAH SELURUHNYA`
dengan kolom realisasi/sisa/%/pagu. Parser cari `%` terdekat sebelum JUMLAH +
angka terbesar setelahnya (pagu). Kalau struktur PDF berubah, parser perlu disesuaikan.
