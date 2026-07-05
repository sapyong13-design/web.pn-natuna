# HANDOFF - Rebuild Website PN Natuna

Dokumen ini pegangan cepat kalau kerja pindah device. Repo ini berisi rebuild website Pengadilan Negeri Natuna Kelas II berbasis Joomla.

## Status Terakhir

- Repo: https://github.com/sapyong13-design/web.pn-natuna.git
- Branch kerja: continue-joomla-rebuild-polish
- Folder lokal utama: C:\tmp\web.pn-natuna
- Local URL terakhir: http://127.0.0.1:8081 (PHP built-in server, Laragon MySQL di C:\laragon)
- Database lokal: pn_natuna_rebuild
- Dump database terakhir: database/pn_natuna_rebuild_20260704_1730.sql (sebelumnya: pn_natuna_rebuild_20260703_1630.sql)
- Stack lokal: PHP 8.3.30 (C:\laragon\bin\php), MySQL 8.4.3 (C:\laragon\bin\mysql)

## Perubahan Sesi 04 Jul 2026 (liat commit message untuk detail lengkap)

- Tipografi: font global Plus Jakarta Sans (body) + Fraunces (heading), ganti Trebuchet/Times.
- Role Model & Instagram: box diseragamkan, frame dihapus (clean), gambar natural no-crop, dots pill.
- Image Joko Ciptanto di-pad ke rasio 3:4 (blurred-fill) supaya sama ukuran Marihod, no crop.
- Jadwal Sidang: empty state "Tidak ada sidang hari ini" (centered), bukan "Data Tidak diTemukan".
- Footer: 3 kolom (kontak+logo, tautan instansi, media sosial), copyright paling bawah kiri, back-to-top button, mobile sosial icon-only.
- Feed Instansi: compaction (spacing lebih rapet), MA RI pakai judul ASLI scrape via browser (Cloudflare blokir PHP), normal case. Logo PT Kepri pakai logonya sendiri (logo-pt-kepri.png).
- Cron auto-refresh: script `cron-refresh-instansi.php` + handoff `CRON-AUTOUPDATE-HANDOFF.md` untuk cPanel (Badilum+PT live, MA manual).
- Sidebar: hapus module Indeks Pelayanan Publik + Tautan Website (unpublished di DB).
- DB dump baru: database/pn_natuna_rebuild_20260704_1730.sql
- Sidebar: card **Indeks Pelayanan Publik** (SKM/IKM + IPAK) di bawah Role Model, sekarang **carousel 2-slide** (ganti tiap 5 detik, label di bawah gambar warna hitam). Auto-refresh Gdrive via `tools/refresh-survey.py`. Folder: `1XVTZjSGKPzM0XPSTlYg4w7f6Ut-QyG7z`.
- Sidebar: widget **Realisasi Anggaran DIPA** (donut chart CSS conic-gradient) di bawah Indeks Pelayanan Publik. Tampilkan % serapan DIPA 01 & 03 + pagu. Klik → buka PDF gdrive. Auto-refresh via `tools/refresh-dipa.py` (parse "JUMLAH SELURUHNYA" per Unit Organisasi). Folder: `1fVI4UvO54g9u4jdIEjM9EgGGZOS0igNV`.
- Artikel **Sejarah Pengadilan** (id 54) dilengkapi: narasi 6 bagian humanized, text justify, gambar gedung float-kanan (`images/sejarah/sejarah-pn-natuna.jpg`), 2 hyperlink Keppres (3/2008 pembentukan PN Ranai + 2/2023 ubah nama PN Natuna) ke peraturan.bpk.go.id.
- **UI Konsistensi beranda (15 saran)**: hero jadi slider 2-slide auto-rotate 6s (slide 1 Selamat Datang + foto gedung, slide 2 Berita & Pengumuman live dari DB via `hero-slider.php`); chrome module card Joomla 4 dibuat (`html/layouts/chromes/card.php`) — semua module style="card" (main + sidebar) sekarang dapat wrapper `.module-card` seragam; design token `--radius-card: 12px` + `--shadow-card`; sipp/stats/map dari krem-emas jadi putih-abu; heading section distandarkan 1.5rem ink; badge & dots seragam; hover-lift seragam; carousel engine digabung jadi `initCarousel()` generik (role/survey/hero) dengan pause-on-hover + prefers-reduced-motion + aria-pressed.

## Tujuan Rebuild

- Membuat website PN Natuna lebih modern, rapi, cepat dibaca, dan cocok untuk layanan publik pengadilan.
- Tetap memakai Joomla sebagai CMS utama supaya staf bisa lanjut kelola konten.
- Homepage dibuat lebih compact: informasi penting cepat terlihat, kartu tidak terlalu besar, footer lebih bersih.
- Integrasi feed instansi dipakai untuk menampilkan berita dan pengumuman dari MA RI, Badilum, dan PT Kepri.

## Cara Lanjut di Device Lain

1. Clone repo:
   git clone https://github.com/sapyong13-design/web.pn-natuna.git
   cd web.pn-natuna
   git checkout continue-joomla-rebuild-polish

2. Import database dari dump:
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS pn_natuna_rebuild CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root pn_natuna_rebuild < database/pn_natuna_rebuild_20260703_1630.sql

3. Sesuaikan configuration.php kalau path, user database, password, atau host berbeda.

4. Jalankan lokal dari root Joomla:
   php -S 127.0.0.1:8081

5. Buka:
   http://127.0.0.1:8081

## File Penting

- templates/pn_natuna_2026/index.php - struktur template utama dan posisi modul.
- templates/pn_natuna_2026/css/template.css - styling homepage, footer, map, quick links, feed instansi.
- templates/pn_natuna_2026/templateDetails.xml - deklarasi posisi template, termasuk footer-social.
- templates/pn_natuna_2026/instansi-feed.php - fetcher/scraper berita instansi dan renderer homepage.
- images/brand/logo-ma.png - logo emas MA, dipakai untuk header instansi.
- images/brand/logo-badilum.png - logo Badilum yang sempat disimpan.
- images/social/instagram.svg - ikon Instagram footer.
- images/social/facebook.svg - ikon Facebook footer.
- images/social/youtube.svg - ikon YouTube footer.
- database/pn_natuna_rebuild_20260703_1630.sql - snapshot DB lokal terakhir.
- .gitignore - ignore cache/log/runtime lokal.

## Perubahan Homepage Terakhir

- Kotak quick link lama dihapus: PPID Informasi, Biaya Perkara, Kontak PTSP, Standar Layanan.
- Quick link sosial media dipindah ke footer.
- Ikon sosial media memakai SVG lokal, bukan embed eksternal.
- Footer sosial diletakkan di bawah teks Pengadilan Negeri Natuna.
- Map Lokasi Kami dibuat lebih pendek, lebar tetap sama.
- Section informasi instansi dibuat compact.
- Judul besar Berita dan Pengumuman Instansi dihapus sesuai permintaan terakhir.
- Tiap instansi tampil 1 baris/kartu sendiri.
- Dalam tiap instansi: kolom kiri Berita, kolom kanan Pengumuman.
- Tiap kolom maksimal 5 item, dengan tanggal posting.
- Logo instansi kecil ditaruh kiri judul instansi.
- Teks judul instansi dibuat putih dan lebih besar.

## Feed Instansi

Source of truth sekarang: templates/pn_natuna_2026/instansi-feed.php.

Perilaku:

- Cache feed: cache/pn_natuna_instansi_feed.json.
- TTL cache: 1 jam.
- Badilum: fetch/scrape live untuk berita dan pengumuman.
- PT Kepri: fetch/scrape live untuk berita dan pengumuman.
- MA RI: situs terblokir Cloudflare dari PHP/curl server-side (halaman challenge, bukan konten). Fallback MA sekarang berisi judul ASLI terbaru hasil scrape via browser real (Chromium). Refresh manual berkala karena cron tidak bisa bypass Cloudflare.

Catatan penting:

- Jangan klaim MA RI live 100% sampai Cloudflare/API resmi selesai.
- Kalau ingin update lebih aman, pakai RSS/API resmi bila tersedia.
- Kalau struktur HTML situs sumber berubah, parser di instansi-feed.php mungkin perlu disesuaikan.

Auto-refresh harian: lihat **CRON-AUTOUPDATE-HANDOFF.md** untuk setup cron job cPanel (Badilum + PT Kepri live, MA RI refresh manual/semi-otomatis). Script cron: `cron-refresh-instansi.php`.

## Instagram dan Jadwal Sidang

- Instagram embed lama bisa refused to connect karena pembatasan iframe dari Instagram/browser.
- Untuk feed Instagram benar-benar update otomatis per jam, perlu API/token resmi atau service embed yang memang diizinkan.
- Kalau hanya pakai iframe/post embed statis, posting terbaru belum tentu ikut update.
- Jadwal sidang/SIPP perlu dicek lewat file terkait sebelum ubah, terutama caching dan sumber datanya.

## Data DB yang Sudah Disimpan

Beberapa perubahan homepage ada di DB Joomla, bukan cuma file:

- Module quick links tersisa dirapikan.
- Module sosial footer dipindah ke posisi footer-social.
- Module statis Berita Instansi Terkini dinonaktifkan karena diganti renderer PHP.

Karena itu dump database/pn_natuna_rebuild_20260703_1630.sql wajib di-import saat pindah device.

## Hal yang Sengaja Tidak Dibuat Dulu

- Belum tambah dependency baru untuk feed.
- Belum bikin panel admin khusus untuk feed instansi.
- Belum bikin integrasi API Instagram.
- Belum bikin cron/job production.
- Belum final deployment cPanel/production.

Tambah nanti kalau:

- Feed harus 100% stabil lintas perubahan HTML situs sumber.
- Instagram wajib selalu posting terbaru otomatis.
- Staf butuh edit source feed dari Joomla admin.
- Situs sudah siap naik production.

## Checklist Lanjut Besok

- Buka http://127.0.0.1:8081 dan cek homepage visual.
- Cek bagian feed instansi: tanggal, judul, spacing, logo.
- Cek footer sosial: ikon, posisi, link klik.
- Cek map Lokasi Kami di desktop dan mobile.
- Cek apakah cache feed perlu dibersihkan saat testing: hapus cache/pn_natuna_instansi_feed.json.
- Kalau mau update Instagram otomatis, putuskan mau pakai API/token resmi atau embed/service pihak ketiga.
- Kalau mau produksi, backup file + DB dulu sebelum upload.

## Prinsip Kerja

- Joomla-native dulu kalau cukup.
- Custom code hanya kalau modul/template Joomla tidak cukup cepat untuk kebutuhan.
- Jangan commit cache, log, runtime lokal.
- Jangan masukkan password/token ke Git.
- Backup DB sebelum ubah struktur atau migrasi.
