# HANDOFF - Rebuild Website PN Natuna

Dokumen ini pegangan cepat kalau kerja pindah device. Repo ini berisi rebuild website Pengadilan Negeri Natuna Kelas II berbasis Joomla.

## Status Terakhir

- Repo: https://github.com/sapyong13-design/web.pn-natuna.git
- Branch kerja: continue-joomla-rebuild-polish
- Folder lokal utama: C:\tmp\web.pn-natuna
- Local URL terakhir: http://localhost:8000 (PHP built-in server, Laragon MySQL di C:\laragon) — WAJIB port 8000 karena `live_site` di configuration.php = http://localhost:8000; port lain kena redirect-loop SEF.
- Database lokal: pn_natuna_rebuild
- Dump database terakhir: database/pn_natuna_rebuild_20260706_2231.sql (sebelumnya: pn_natuna_rebuild_20260706_1215.sql)
- Stack lokal: PHP 8.3.30 (C:\laragon\bin\php), MySQL 8.4.3 (C:\laragon\bin\mysql)

## Perubahan Sesi 06 Jul 2026 malam (dark mode toggle, Layanan Publik, role model & IG revert)

SQL: `database/_layanan_publik_redesign.sql` + `database/_role_model_instagram_restore.sql` (keduanya sudah di-apply, termasuk di dump 20260706_2231). CSS baru di blok `/* LAYANAN PUBLIK 2026-07 */` akhir template.css.

- **Tombol dark mode di topbar** (index.php, sebelah jam): ikon bulan/matahari SVG, class `dark-toggle` (handler JS sudah ada sebelumnya, tombolnya yang belum pernah dibuat). **Dark mode TIDAK lagi default ikut sistem** — inline script `<body>` dan template.js kini hanya cek `localStorage pnNatunaDark === '1'`; default selalu terang, aktif hanya via tombol.
- **Redesign 8 halaman menu Layanan Publik** (artikel 26, 11, 12, 13, 14, 15, 19, 97): hero topik + ilustrasi SVG inline duotone maroon-emas per halaman, subnav pil antar-halaman (aria-current), kartu berikon (`svc-card`), langkah bernomor (`svc-steps`), dokumen zoom (pakai lightbox global `data-maklumat-zoom`), CTA band, chip fakta flat (beda visual dari pil navigasi). Dokumen `biaya-jenis-layanan.png` + `waktu-layanan.png` yang sebelumnya tak terpakai kini tampil di halaman PTSP (zoomable).
- **`setupDynamicServiceHours()` diperluas**: dari 1 elemen `#dynamic-service-hours` jadi `querySelectorAll('#dynamic-service-hours, .js-service-hours')` — chip "Jam layanan hari ini" di halaman layanan ikut ter-update (Jumat 08.00-17.00, akhir pekan Tutup).
- **Role Model (modul 482) DIKEMBALIKAN ke poster khusus** `/images/role-model/joko-ciptanto-role-model-2026.png` + `marihod-tua-lubis-role-model-2026.jpg` (versi kartu HTML foto-profil-pegawai dari sesi siang dibatalkan — poster lebih bagus). Markup persis versi dump 20260706_0020.
- **Instagram (modul 483) kembali ke slider post individual, kini 9 POST TERBARU** — profile embed dibatalkan: grid-nya hanya me-render 6 tile (baris ke-3 lazy-load tak pernah jalan di iframe scrolling=no) dan thumbnail-nya ter-crop. Slider phone-frame lama dipakai lagi dengan 9 slide + 9 dots + tombol Ikuti (class baru `instagram-follow-standalone`).
- **CARA UPDATE 9 POST IG (penting, manual per periode):** shortcode TIDAK bisa di-scrape langsung (API 429, DOM embed tanpa link). Trik yang terbukti: render `https://www.instagram.com/pn.natuna/embed/` via reader/headless → ambil `ig_cache_key` di URL gambar (base64 dari media PK) → decode → konversi PK ke shortcode dengan base64url alphabet `A-Za-z0-9-_` (bagi 64 berulang). Script konversi contoh: C:\tmp\ig-codes.php (di luar repo). Validasi: hasil decode cocok dengan shortcode lama `DZm_mS3BaLT`. Lalu update modul 483 (geser slide, buang terlama).
- Verifikasi: screenshot headless Edge desktop/mobile — 8 halaman layanan ✓ (hero+ilustrasi+chip dinamis terisi JS), role model poster ✓, IG slider berputar otomatis + post utuh tanpa crop ✓. Overflow mobile 390px di headless = artefak lama yang sudah dikenal (lihat catatan sesi pagi).

## Perubahan Sesi 06 Jul 2026 sore-2 (UI Polish putaran 2, 10 item)

SQL modul: `database/_ui_polish_homepage_2.sql` (sudah di-apply, termasuk di dump 20260706_1215).

- ~~**Instagram AUTO-UPDATE** (modul 483)~~ **[DIBATALKAN sesi malam — grid embed cuma render 6 tile & ter-crop; lihat seksi sesi malam]**: 5 iframe post hardcoded diganti **1 profile embed resmi** `https://www.instagram.com/pn.natuna/embed/` — selalu menampilkan grid 6 post TERBARU + follower count, tanpa cron/token/scraping (endpoint scraping server-side sudah diuji: 429/login-wall, tidak feasible). Lazy-load via `setupLazyIframes()` (IO rootMargin 400px). Tombol "Ikuti @pn.natuna" di bawahnya.
- **Berita Terbaru bergambar**: `pn_natuna_render_latest_news()` di hero-slider.php — 3 kartu (foto artikel, tanggal, judul Fraunces, excerpt clamp-2, badge Baru) di kolom utama sebelum Kabar Instansi; mobile jadi layout horizontal foto-kiri.
- **Jadwal Sidang redesign** (sipp-schedule.php): tabel 7 kolom → kartu per sidang (nomor perkara Fraunces + chip ruangan/agenda/sidang-keliling + tombol Detil); empty state berikon + CTA SIPP; varian dark mode lengkap.
- **Kartu "Kinerja & Akuntabilitas"** (modul 816): gabungan skor SKM/IPAK + widget DIPA (modul 817 di-unpublish). **tools/refresh-dipa.py diarahkan ke modul 816** dan sekarang hanya me-replace blok `<div class="dipa-widget">` (regex, teruji round-trip) — konten skor survei tidak tertimpa.
- **SEO/share**: meta viewport dari sesi sebelumnya + kini og:title/description/image (images/brand/og-image.jpg 1200×630), twitter card, theme-color maroon, favicon 32/180/512 (images/brand/), JSON-LD GovernmentOrganization (alamat, telepon, jam buka), fallback meta description via setDescription().
- **Dark mode ikut sistem**: inline script setelah `<body>` menerapkan is-dark bila `prefers-color-scheme: dark` DAN user belum pernah memilih (localStorage pnNatunaDark null); toggle manual tetap menang. Varian gelap baru: kartu jadwal, kartu berita, map card (iframe di-invert), instagram card, dipa-subhead.
- **Statistik pengunjung pindah ke footer** (baris kecil ikon mata: total/hari ini/online); kartu sidebar dihapus. ⚠️ CATATAN INTEGRITAS: `stats-counter.php` menambah **base_offset 24.500 palsu** ke total kunjungan (baris asli ±330) — pertimbangkan dihapus sebelum produksi. "Anomali" 15.323 kemarin cuma animasi count-up yang tertangkap screenshot.
- **Quick links**: typo "terpad." → "terpadu." (modul 112), deskripsi line-clamp 2 baris.
- **Kontras**: --color-muted #667481 → #57646f (≥4.5:1 di putih); dots slider lebih besar di layar sentuh (pointer:coarse).
- **Foto hero**: Ken Burns diperkecil (1.01→1.045) untuk kurangi blur upscale. ⚠️ **Foto gedung hanya 700×523px di-stretch ke 1440px+ — PERLU FOTO HD ≥1920px** (foto ulang gedung landscape, idealnya golden hour). Tidak ada kandidat lain di repo (banner.jpg = sample Joomla).
- Verifikasi: CDP light/dark/mobile — news 3 kartu ✓, sipp kartu ✓, DIPA tunggal di 816 ✓, typo fix ✓, footer stats ✓, IG iframe lazy ✓, mobile tanpa overflow ✓.

## Perubahan Sesi 06 Jul 2026 sore (perbaikan performa scroll)

Keluhan: beranda terasa berat / tidak 60fps saat scroll setelah UI polish. Diagnosa via Chrome CDP (headed + CPU throttle 4×): di mesin dev semua varian tetap 133-144fps, jadi yang dihilangkan adalah seluruh kelas beban kontinu baru + masalah cache:

- **Cache-busting `?v=filemtime`** untuk template.css/fonts.css/template.js di index.php — `php -S` (dan Apache produksi) memicu heuristic caching; browser bisa menjalankan campuran CSS/JS lama+baru setelah update (terbukti terjadi saat pengujian). Kemungkinan besar ini sumber utama keluhan.
- **Semua `backdrop-filter: blur` dihapus** (chip hero, panel berita, panah hero, chip foto, mobile quick bar) — re-filter tiap frame di atas layer Ken Burns yang beranimasi; diganti background rgba lebih pekat, tampilan nyaris identik. Jangan tambah backdrop-filter di atas elemen yang beranimasi.
- **Ken Burns di-pause saat hero keluar viewport** (`setupHeroBackdropPause()` + `.is-offstage`) + `will-change: transform`.
- **Scroll reveal dipercepat**: durasi 0.55s→0.38s, translate 16→10px, dan pre-reveal 200px SEBELUM elemen masuk viewport (rootMargin positif) — kartu tidak pernah terlihat kosong saat scroll cepat (persepsi "berat" hilang).
- **joko-ciptanto.jpg 2184×2403 (1MB) → 900×990 (142KB)**.
- Verifikasi CDP: backdrop-filter tersisa 0, pause/resume Ken Burns bekerja, scroll throttle 4× = 133fps (0,4% frame >20ms).
- Catatan: total kunjungan statistik pengunjung tampak berubah-ubah (24.502 → 15.323) — penyimpanan stats-counter perlu dicek nanti.

## Perubahan Sesi 06 Jul 2026 (UI Polish beranda, 10 item)

Semua CSS baru ada di blok `/* UI POLISH 2026-07 */` di akhir template.css. SQL modul: `database/_ui_polish_homepage.sql` (sudah di-apply lokal, termasuk di dump 20260706_1050).

- **`<meta name="viewport">` ditambahkan di index.php** — sebelumnya TIDAK ADA, jadi di HP asli situs dirender ~980px lalu di-zoom-out. Ini fix mobile paling fundamental sesi ini.
- **Hero sinematik full-bleed** (hero-slider.php): foto gedung jadi backdrop penuh (img `fetchpriority=high`) + scrim gradien gelap + Ken Burns pelan; teks slide 1 putih di atasnya; slide 2 (Berita & Pengumuman) jadi panel kaca putih; dots progress pindah ke bawah tengah, panah pindah ke kanan-bawah (tidak menimpa teks); chip caption "Gedung PN Natuna" kiri-bawah; interval 7 detik. Slide non-aktif kini juga `visibility:hidden` (hero/role/survey).
- **Jam live pindah dari nav ke topbar** (`.topbar-clock` di index.php) — nav tidak overflow lagi di 1440px. **Sticky nav**: `setupStickyNav()` di template.js + `body.nav-stuck` (fixed + shadow + padding-top kompensasi).
- **Feed instansi jadi tab** (instansi-feed.php render baru): 1 card "Kabar Instansi Peradilan" + tabbar berlogo (MA/Ditjen Badilum/PT Kepri), panel role=tabpanel + keyboard arrow, timestamp "Diperbarui ..." dari mtime cache, link "Kunjungi situs ...". JS `setupInstansiTabs()`. Halaman memendek ±1200px. Logo badilum kini pakai logo-badilum.png.
- **Galeri Fasilitas**: band gelap maroon→hijau via moduleclass_sfx `facility-band` (bukan `:has()` — konten mod_custom terbungkus `div.custom` jadi `:has(> ...)` tidak match); foto ASLI beda per kartu via `.facility-thumb` (PTSP=briefing-ptsp, Disabilitas=**crop baru images/layanan/akses-disabilitas.jpg** dari foto PTSP bagian guiding block, Posbakum=poster posbakum, Lokasi=foto gedung); hover zoom. `.facility-card::before` lama dimatikan.
- **Indeks Pelayanan Publik jadi tile skor** (modul 816): SKM 3,97/4,00 (99,27%) + IPAK 4,00/4,00 (100%), 61 responden TW2 2026, bar emas, klik → lightbox maklumat (data-maklumat-zoom). ⚠️ **Skor diinput manual per triwulan** — saat refresh-survey.py mengganti gambar, update angka di modul 816 juga.
- **Role Model jadi kartu HTML** (modul 482): foto polos (joko-ciptanto.jpg / marihod.png dari images/profil/pegawai/) + badge emas (Role Model 2026 / Agen Perubahan 2026) + nama/jabatan/NIP/SK sebagai teks HTML overlay gradient. Poster lama di images/role-model/ tidak dipakai lagi di beranda.
- **Lokasi Kami**: showtitle=0 (judul dobel hilang).
- **Link Layanan Cepat**: semua logo dinormalisasi jadi badge lingkaran 52px putih + panah → muncul saat hover; mobile scroll-snap diberi mask fade kanan.
- **Mobile polish**: topbar 1 baris (desc/email/jam-live disembunyikan ≤760px), bottom quick-action bar 5 kolom dengan ikon SVG (index.php), tombol WhatsApp & back-to-top & ikon aksesibilitas direposisi agar tidak saling tindih dengan bar bawah, gambar maklumat max-height 54vh.
- **Ritme visual**: kicker emas otomatis di semua judul modul (termasuk h2 di dalam `div.custom`), scroll-reveal IntersectionObserver (`setupScrollReveal()`, guard prefers-reduced-motion), count-up statistik (`setupCountUp()`).
- **Statistik pengunjung**: number_format Indonesia (24.502, bukan 24,502) + data-countup.
- **Font self-host**: fonts/fraunces-var.woff2 + plus-jakarta-sans-var.woff2 (variable, latin subset) + css/fonts.css + preload; Google Fonts CDN dihapus dari index.php.
- Catatan verifikasi: screenshot headless Chrome `--window-size=390` menyesatkan (lebar layout minimum ~400px, konten kanan terpotong padahal layout riil 390px BENAR) — untuk cek mobile akurat pakai harness iframe 390px atau CDP. Interaksi (sticky, reveal, tab, slider, count-up) diverifikasi via Chrome DevTools Protocol.

## Perubahan Sesi 05 Jul 2026

- **Redesign card Maklumat Layanan** (module id 808, posisi home-alerts): dari thumbnail kecil 230px + teks jadi 2 panel "piagam" sebelahan. Tiap panel: eyebrow emas + judul Fraunces, dokumen BESAR (lebar penuh kolom, aspect 8/11 contain, bingkai emas ganda), kutipan isi maklumat asli (italic Fraunces + penandatangan & tanggal), tombol pill "Selengkapnya →". Klik dokumen → **lightbox** fullscreen (Esc/klik luar/tombol tutup, focus management). CSS: blok `.maklumat-duo`/`.maklumat-panel`/`.maklumat-lightbox` di template.css (ganti `.maklumat-compact-*`). JS: `setupMaklumatLightbox()` di template.js. Konten module: `database/_maklumat_redesign.sql` (sudah di-apply ke DB lokal — dump berikutnya harus menyertakan ini). Dark mode + prefers-reduced-motion didukung.
- Catatan: foto maklumat pelayanan (JPG) masih ada background dinding kayu dari foto pigura — idealnya di-crop/scan ulang biar makin bersih.
- **Redesign Profil Hakim (artikel id 58), Profil Kepaniteraan (id 59) & Profil Kesekretariatan (id 60)**: dari staff-card datar jadi **galeri roster berseksi** dengan hierarki. Pimpinan = card featured horizontal (foto 200px, gradient krem + gold rule); anggota = grid 2 kolom **card kompak horizontal** (foto kecil 104px 3:4 rounded, badge pendidikan S1/S2/S3/D3 di pojok foto, nama Fraunces, pill jabatan, NIP & pangkat) — foto sengaja kecil atas permintaan Faris, jangan digedein lagi. Hakim: Pimpinan (Wakil Ketua) → Hakim (5) → Hakim Ad-Hoc Perikanan (5, tanpa baris NIP/pangkat karena "-"). Kesekretariatan: Sekretaris → **Pejabat Struktural (3)**: Candra Firmansyah = Kasubbag Kepegawaian/Ortala, Frans Alberto = Plt. Kasubbag Umum & Keuangan, M. Faris Akbar = Plt. Kasubbag Perencanaan/TI/Pelaporan (update 05 Jul 2026, dulunya kosong) (seksi Staf dihapus — Juprizal, Asturi, Dion **dipindah ke Kepaniteraan** per instruksi Faris 05 Jul 2026, tetap Dokumentalis Hukum, path foto masih di folder kesekretariatan/). Catatan `content-note` "Data dimigrasikan dari arsip... perlu diverifikasi" **sudah dihapus dari 3 halaman** (data sudah diverifikasi Faris 05 Jul 2026); lead 3 halaman dirapikan ke rumusan formal ("melaksanakan dukungan...", konsisten Perma 7/2015). Kepaniteraan: Panitera Hadry B. (featured, **merangkap Plt. Panmud Perdata + Plt. Panmud Khusus Perikanan + Plt. Jurusita**, pill via `.roster-role-row`) → Pejabat Struktural (2: Jhivo = **Plt. Panmud Hukum** + Panitera Pengganti, Ari = **Plt. Panmud Pidana** + Panitera Pengganti) → Staf (5: Marihod = Analis Perkara, Cania = Pengelola Perkara, + Juprizal/Asturi/Dion = Dokumentalis Hukum). Tidak ada jabatan kosong (seksi Belum Terisi dihapus). CSS: blok `.roster-*` di template.css (class `.staff-*` lama TIDAK dihapus — masih dipakai Profil PPPK id 114). Konten: `database/_roster_redesign.sql` (sudah di-apply DB lokal). Dark mode + responsive (2→1 kolom) didukung. Kalau cocok, pola roster bisa diterapkan juga ke PPPK.
- **Redesign 4 sub-halaman kepaniteraan** (Tentang Pengadilan → id 107 Pidana, 108 Perdata, 109 Hukum, 110 Khusus Perikanan; URL `/profil-pengadilan/kepaniteraan-*`): tiap halaman kini punya seksi **Penanggung Jawab** (roster-card Plt. Panmud sesuai struktur: Pidana=Ari, Perdata & Perikanan=Hadry, Hukum=Jhivo), **Ruang Lingkup/Layanan Permohonan** jadi card checklist (`.unit-scope`, centang emas; varian `.unit-scope-2` untuk grid 2 kolom), dan **bagan alur dibingkai & zoomable** (`.maklumat-doc.alur-frame` + lightbox maklumat yang sudah ada — `data-maklumat-zoom` bekerja global via template.js; override aspect-ratio harus pakai selector `.maklumat-doc.alur-frame img` karena kalah urutan file). Frasa "sebelum publikasi produksi" di Catatan Persyaratan hukum diganti "dapat dikonfirmasi melalui PTSP". Konten: `database/_unit_kepaniteraan.sql` (sudah di-apply DB lokal). Path gambar di 4 halaman ini pakai absolut `/images/...`.
- **Menu "Profil PPPK" baru** (id 278, anak Tentang Pengadilan setelah Profil Kesekretariatan, URL `/profil-pengadilan/profil-pppk` → artikel id 114) + **tag Joomla "PPPK"** (pnn_tags id 2) — SQL: `database/_menu_tag_pppk.sql`. **Artikel Profil PPPK (id 114) di-redesign roster** (`database/_pppk_redesign.sql`): data diverifikasi terhadap situs live pn-natuna.go.id 06 Jul 2026 (9 orang, cocok), dikelompokkan Penata Layanan Operasional (2, gol IX) & Operator Pelayanan Operasional (7, gol V), badge pendidikan SMA/SMK/MA/S1, pill "PPPK" di tiap card (pola pill ini bisa dipakai menandai PPPK di halaman kepaniteraan/kesekretariatan). ⚠️ NIP Ardiansyah `1990010620252101027` = 19 digit (NIP normal 18) — kemungkinan typo sejak website lama, perlu konfirmasi kepegawaian.
- **Distribusi PPPK ke bagian** (SQL: `database/_pppk_placement.sql`, instruksi Faris 06 Jul 2026): kolom Golongan dihapus dari halaman PPPK (diganti baris **Penempatan**); "Ratih Pusita" dikoreksi jadi **"Rati Pusita, S.Pd.I."** (file foto tetap ratih.png). Kesekretariatan dapat seksi **Staf (6 PPPK)**: Bait=Subbag PTIP, Rati/Ria/Riko/Kusnaidi=Subbag Umum & Keuangan, Noki=Subbag Kepegawaian/Ortala. Kepaniteraan Staf 5→**8**: + Yuningsih=Kep. Pidana, Kartina=Kep. Perdata, Ardiansyah=Kep. Hukum. Card PPPK di halaman bagian: pill [unit] + [PPPK], meta NIP saja. Halaman unit kepaniteraan (107-110) TIDAK diberi daftar staf (hanya Penanggung Jawab) — tambah kalau diminta.
- **Pill unit untuk staf PNS kepaniteraan** (juga di `_pppk_placement.sql`): Marihod & Juprizal = Kepaniteraan Pidana, Asturi & Cania = Kepaniteraan Perdata, Dion = Kepaniteraan Hukum. Total pill unit di halaman kepaniteraan: Pidana 3 (+ Yuningsih), Perdata 3 (+ Kartina), Hukum 2 (+ Ardiansyah).
- **Dump DB baru: `database/pn_natuna_rebuild_20260706_0020.sql`** — semua perubahan konten/menu/tag sesi 05-06 Jul sudah termasuk. Class `.staff-*` lama kini TIDAK dipakai halaman mana pun (bisa dihapus dari template.css nanti kalau mau bersih-bersih).
- **Menu 4 unit kepaniteraan dipindah jadi sub-submenu Profil Kepaniteraan** (Tentang Pengadilan → Profil Kepaniteraan → Pidana/Perdata/Hukum/Perikanan). SQL: `database/_menu_kepaniteraan_nest.sql` — nested set diatur manual (aman karena item 212-216 menempati lft 1016-1025 berurutan; kalau nanti pindah-pindah menu lagi lebih baik lewat admin Joomla biar rebuild otomatis). **URL berubah**: `/profil-pengadilan/kepaniteraan-*` (404) → `/profil-pengadilan/profil-kepaniteraan/kepaniteraan-*`. Tidak ada link lama hardcoded di konten/module (sudah di-scan). Template sudah support flyout level 3 (`.main-menu li ul ul`) + mobile nested.

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
