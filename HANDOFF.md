# HANDOFF — Rebuild Website PN Natuna

Knowledge base status aktif untuk rebuild Joomla Pengadilan Negeri Natuna Kelas II. Kronologi Juli 2026 dipindahkan ke [`docs/archive/HANDOFF-history-2026-07.md`](docs/archive/HANDOFF-history-2026-07.md).

## Lingkungan kerja

- Repo: <https://github.com/sapyong13-design/web.pn-natuna.git>
- Branch kerja: `continue-joomla-rebuild-polish`
- Root lokal: `C:\tmp\web.pn-natuna`
- URL lokal: `http://localhost:8080`; port harus sama dengan `live_site` di `configuration.php` agar SEF tidak redirect-loop.
- Database lokal: `pn_natuna_rebuild`
- Snapshot penuh terbaru: `database/pn_natuna_rebuild_20260715_deploy_exact.sql` (salinan `.sql.gz` tersedia; lokal, tidak dilacak Git). Setelah restore, selalu jalankan seluruh migrasi kanonis agar perubahan 16–21 Juli diterapkan.
- Stack lokal: PHP 8.3.30 (`C:\laragon\bin\php`), MySQL 8.4.3 (`C:\laragon\bin\mysql`)

Konten artikel dan modul hidup di DB. Setiap perubahan DB yang wajib mengikuti kode harus berupa migrasi SQL idempoten baru di `database/migrations/`; restore dump wajib melalui `tools/restore-local-db.py` agar seluruh migrasi diputar ulang.

## Operasi cron cPanel aktif

- Setup updater staging `new.pn-natuna.go.id`, path aktual akun `pnnatuna`, command refresh manual, command per sumber, format Google Drive, dan troubleshooting Python dicatat di [`CRON-AUTOUPDATE-HANDOFF.md`](CRON-AUTOUPDATE-HANDOFF.md), bagian **Status cPanel Aktual**.
- Refresh manual semua sumber: `set -a; . /home/pnnatuna/private/cron/pn-natuna.env; set +a; /bin/sh "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-all.sh"`.
- Jangan commit `/home/pnnatuna/private/cron/pn-natuna.env`, `mysql.cnf`, password, atau isi log. Private checkout hanya menyimpan kode; konfigurasi/kredensial tetap di luar webroot.

## Lanjut besok — deploy staging commit `149f88e`

- Permintaan berikutnya bila Faris mengatakan **deploy/up ke new PN Natuna**: jalankan prosedur staging di [`CRON-AUTOUPDATE-HANDOFF.md`](CRON-AUTOUPDATE-HANDOFF.md), bagian **Deploy Staging Commit 149f88e**. Tidak perlu meminta ulang urutan deployment.
- Target hanya `https://new.pn-natuna.go.id`; private checkout `/home/pnnatuna/repos/web.pn-natuna`; webroot dan DB wajib dibaca dari `/home/pnnatuna/private/cron/pn-natuna.env`.
- Commit GitHub aktif: `149f88e feat: polish court information experiences`, branch `continue-joomla-rebuild-polish`.
- Urutan wajib: muat env → pastikan `.pn-natuna-staging` berisi `new.pn-natuna.go.id` → pull `149f88e` → backup DB tervalidasi → deploy helper dengan `--no-pull` → migrasi normal → validasi menu → cache clean → refresh cron → smoke test.
- Larangan: jangan `--reapply`, `--full-staging`, `--reset-database`, atau mengarah ke document root domain utama. Jangan mengubah Python Application URL/virtualenv saat deploy Joomla.
- Bila `git status` private checkout tidak bersih, backup gagal, marker salah, migrasi gagal, atau nested-set tidak valid: berhenti dan tampilkan output; jangan memaksa lanjut.

## Handoff deployment staging 21 Juli 2026

- Paket fitur aktif di GitHub: commit `32b7274` (**transparency archives, kartu Jam Layanan, Sambutan Wakil Ketua**) ditambah fix runner migrasi `eab76e4` dan `45423b0`; branch `continue-joomla-rebuild-polish` sinkron sampai `45423b0`.
- Staging: `https://new.pn-natuna.go.id`; private checkout `/home/pnnatuna/repos/web.pn-natuna`; webroot berasal dari `PN_NATUNA_JPATH_ROOT` di `/home/pnnatuna/private/cron/pn-natuna.env`.
- Deployment belum selesai. Registry staging terakhir hanya memuat `20260803_align_transparency_document_cards.sql` untuk batch baru. `20260804` belum tercatat dan belum diterapkan karena retry berhenti pada konflik collation.
- Fix `45423b0` mengubah `tools/apply-db-migrations.py` agar mengambil charset/collation langsung dari `pnn_content.introtext`, bukan default schema. Langkah pertama besok: `git pull --ff-only origin continue-joomla-rebuild-polish`, pastikan HEAD `45423b0`, lalu retry tanpa `--reapply`.
- Sebelum retry, WAJIB pastikan backup SQL privat tabel `pnn_menu`, `pnn_content`, `pnn_modules`, dan `pnn_project_migrations` tersedia dan tidak kosong. Migrasi `20260810` membangun ulang nested-set menu sehingga backup menu wajib dapat dipulihkan.
- Command retry:
  `"$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/apply-db-migrations.py" --mysql "$MYSQL_BIN" --mysql-defaults-file "$MYSQL_DEFAULTS_FILE" --database "$DB_NAME"`
- Target retry: skip `20260803`; apply `20260804` sampai `20260810`. Jangan memakai `--reapply` di staging.
- Sesudah sukses: validasi nested-set (`lft < rgt`, tidak ada duplikat boundary), jalankan `cd "$PN_NATUNA_JPATH_ROOT" && "$PHP_BIN" cli/joomla.php cache:clean`, kemudian `/bin/sh "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-all.sh"`.
- Batch memuat file template selain migrasi: `templates/pn_natuna_2026/css/template.css`, `js/template.js`, dan `html/com_content/article/default.php`. `git pull` private checkout tidak memperbarui webroot; ketiga file wajib disalin dan diverifikasi dengan `cmp` terhadap webroot.
- Halaman/fitur target: `/profil-pengadilan/kata-sambutan` (Joko Ciptanto, S.H., M.H., jabatan terverifikasi **Wakil Ketua**), Jam Layanan dinamis, Laporan Tahunan 2023, DIPA April 2026, IKM/IPAK TW I–II 2026, dan Survei Harian Januari–Juni 2026.
- Sumber resmi 2026 mengonfirmasi Joko Ciptanto sebagai Wakil Ketua; jangan mengganti menjadi Ketua tanpa sumber resmi baru.

## Status antarmuka saat ini

- Hero beranda: **slider tiga slide** — sambutan/layanan, poster **Tolak Gratifikasi & Pungutan Liar**, lalu Berita & Pengumuman. Backdrop pre-graded `images/hero/gedung-pn-natuna-2026-graded.webp` dengan `fetchpriority=high`, feather mask 11%, dan animasi zoom yang dipause selama scroll. Kontrak: `tools/test_integrity_hero_slide.php`.
- Poster Zona Integritas ditampilkan **utuh** sebagai slide dua (`images/hero/integritas-tolak-gratifikasi-pungli-2026.webp`, 1672×941, 211KB, `object-fit:contain`), seluruh artwork menaut ke `/zona-integritas`. Posternya harus terlihat sebagai poster; ini keputusan yang sudah diambil, bukan preferensi teknis.
- **Dua percobaan mengganti slider pada 25 Jul 2026 sudah ditolak dan di-revert. Jangan diulang tanpa permintaan eksplisit:** (a) grid dua kolom dengan `.hero-news-card` + `.hero-integrity` ditempel di paruh kanan foto — dua bahasa desain bertabrakan, gedung tertutup scrim di kiri dan kartu di kanan, dan pratinjau poster 92px tidak terbaca; (b) tiga lapis melebar `.hero-stage` / `.hero-newsbar` / `.hero-pledge` — Zona Integritas menyusut jadi satu baris teks dan kehilangan posternya. Riwayat keduanya ada di commit `a83f18d` dan `dcc7551` bila perlu dirujuk.
- Ribbon hero memuat tiga data unik: agenda sidang hari ini dari cache SIPP, IKM, dan IPAK berikut jumlah responden masing-masing dari modul **816**. Helper `pn_natuna_hero_agenda_today()` dan `pn_natuna_hero_survey_scores()` keduanya fail-safe: baris hilang bila sumbernya kosong, hero tidak pernah error.
- Role Model, modul **482**: dua poster dari `images/role-model/` (Joko Ciptanto kini `joko-ciptanto-role-model-2026.webp`).
- Instagram: homepage merender carousel editorial langsung ke post dari cache JSON + thumbnail WebP lokal (`cache/pn_natuna_instagram/`, `media/instagram/`; ter-ignore), dengan modul **483** sebagai fallback saat cache invalid. Header profil, tombol Ikuti, dan dots dihapus; foto selalu utuh via `object-fit:contain`, caption dua baris berada di bawah media, tinggi card stabil lintas rasio, dan footer hanya memuat chevron minimal + counter `n dari 6`. Dark mode memakai letterbox/caption surface khusus. Refresh memakai `tools/cron-refresh-instagram.php` dan RSS.app privat per jam. **Status 2026-07-16:** cache terakhir 11 Juli; RSS.app berhenti memberi post valid dan URL feed hanya tersedia di private env server. Periksa/regenerasi feed RSS.app serta `INSTAGRAM_RSS_URL`, jangan commit URL privat.
- Kabar Instansi: MA memakai Google News RSS yang difilter 60 hari, diurutkan `pubDate`, dibersihkan dari homepage/noise, dinormalisasi dari ALL CAPS, lalu dipadukan fallback terkurasi sampai lima item; pengumuman MA memakai fallback bila RSS basi. Badilum dan PT Kepri live; filter PT Kepri tidak lagi membuang judul yang memuat Ketua/Wakil. Cache menyimpan `_status` sumber dan cron mencatat live/fallback; renderer tetap hanya tiga tab. Regression test `tools/test-instansi-feed.php`.
- Pengumuman & Video Terbaru: homepage memakai satu outer card berisi heading/actions dan dua panel internal setara `50:50` desktop; media kedua panel `16:9`, padding/tinggi/surface konsisten. Mobile menempatkan video sebelum pengumuman dengan divider setelah video. Maksimal lima video channel resmi `UCuPb35OggK2PKdW7Ed0qszA`; dua video wajib selalu di awal: `-Di2t-yUZ1I` dan `kQ0dMRp1W_g`, lalu tiga video Atom terbaru unik. Data dibaca dari `cache/pn_natuna_youtube/feed.json`; `tools/cron-refresh-youtube.php` mempertahankan cache valid saat feed gagal. Iframe `youtube-nocookie.com` baru dibuat setelah klik Putar.
- Polish 18 Jul 2026: preview Pengumuman dan Video memakai shell visual yang sama; action header menjadi tombol sekunder identik. Dark mode mobile mencakup outer card, label, counter, active video item, divider, dan caption tanpa residu surface terang.
- Mode gelap: default terang, aktif hanya melalui tombol dan `localStorage` key `pnNatunaDark`; tidak mengikuti preferensi sistem.
- Mobile `≤760px`: header kompak/sticky, drawer bertingkat, bottom bar lima aksi hanya di homepage, sidebar menjadi snap rail.
- Audit navigasi mobile 18 Jul 2026: label utama menjadi **Berita & Pengumuman** dan **Kontak**; Area I–VI memakai nama deskriptif; item internal Penginputan Data Eksekusi tidak dipublikasikan. Drawer menyisipkan link Ringkasan setiap parent serta heading visual Akuntabilitas/Keuangan/Survei/Informasi Publik dan Biaya & Prosedur/Data & Administrasi tanpa mengubah parent route Joomla. Shortcut permohonan informasi/pengaduan memakai route kanonis. Footer 68px; status mode gelap tunggal `Mati`/`Aktif`, ikon marun pada terang dan emas pada gelap. Migrasi berurutan `20260722_mobile_navigation_information_architecture.sql` lalu `20260723_preserve_mobile_menu_routes.sql`; kontrak `tools/test_mobile_menu_migration.py` dan `tools/test_mobile_navigation_audit.php`. Seluruh 65 route component mainmenu terpublikasi terverifikasi HTTP 200 lokal.
- Comprehensive mobile polish 18 Jul 2026: drawer memiliki filter menu lokal offline, clear, live status, dan empty state. Quick-links strip serta WhatsApp floating disembunyikan di homepage mobile karena duplikat; WhatsApp tetap ada di drawer/bottom bar. Back-to-top muncul setelah 900px. YouTube mobile menampilkan tiga item, rail dan sidebar memiliki hint/counter serta scrollbar tersembunyi. Font navigasi/metadata/bottom bar dinaikkan, touch feedback menghormati reduced motion. QA 320×568, 390×844, dan text zoom 200% menghasilkan overflow horizontal 0. Catatan 25 Jul 2026: butir hero tiga slide, CTA poster, panah hero, dan prefetch poster idle tidak berlaku lagi sejak rotasi dibongkar.
- Konsistensi visual homepage 18 Jul 2026: wrapper section utama memakai lebar/inset seragam; Lokasi Kami full-width pada wrapper desktop. Dark mode menyatukan topbar/header/body/quick-links, memakai logo BerAKHLAK raster khusus dark (`images/brand/logo-asn-berakhlak-dark.png`) melalui migrasi `20260725_dark_header_brand_badges.sql`, dan tetap mempertahankan artwork AMPUH asli.
- Dropdown desktop dan submenu mobile semuanya rata kiri; group label Transparansi memakai hierarki terang/emas dan touch target link minimal 44px. Tab sumber Kabar Instansi mobile membungkus dari kiri.
- Detail artikel kategori Berita/Pengumuman memakai override `templates/pn_natuna_2026/html/com_content/article/default.php`; kategori lain memakai template core secara langsung.
- Transparansi dan keluarga Profil Pengadilan sudah memakai route canonical, shell terakses, state fokus/gelap/reduced-motion, dan konten DB terbaru.
- Editorial 2026-07-11: section homepage memakai pola `.section-kicker → h2 → .section-desc → konten → satu aksi` (7 section), nav desktop menandai route aktif via `li.active/.current` underline gold, 3 divider statis `.home-section-divider`, board Jadwal/Instansi berlatar `--color-soft`. Blok CSS: `/* EDITORIAL 2026-07-11 */` dan `/* PERF-MOTION 2026-07-11 */` di akhir `template.css`.
- Performa: token shadow `--shadow-subtle/card/overlay`, reveal one-shot maks 10px/380ms (opacity+transform saja), `content-visibility:auto` pada 4 section bawah fold, gambar berat dikonversi WebP (berita pelantikan 157KB, maklumat 332KB, role model 65KB; original di `images/_originals/`, gitignored).
- Maklumat modul **808**: `showtitle=0`; chrome hanya menampilkan kicker **Layanan Publik**, tanpa heading/deskripsi redundan. Outer section memakai card homepage, dua dokumen berdampingan dengan thumbnail lebih besar di desktop, satu kolom di mobile, lightbox tetap aktif. Migrasi: `20260724_polish_homepage_maklumat_card.sql`.
- **Stabilitas layout 25 Jul 2026 — CLS 1,109 → 0,020 (desktop 1366).** Empat sumber geseran. Tiga pertama pola yang sama, "kotak tidak dicadangkan sebelum JS mengisi":
  1. `#live-clock-date`/`#live-clock-time` kosong di HTML lalu diisi JS. Sekarang dirender server-side di `index.php` (`Factory::getDate('now','Asia/Jakarta')`); JS hanya memperbarui detik, dan `#live-clock-time` punya `min-width:12ch` supaya detik berdetak tanpa reflow.
  2. `#dynamic-service-hours` berisi placeholder `08.00-16.30 WIB` (117px) lalu diganti JS dengan salah satu dari tiga nilai; yang terpanjang `Tutup (Libur Akhir Pekan)` (164px). Selisih itu membalik keputusan wrap `.topbar-info`. Lebarnya kini dikunci `min-width:12.5em`.
  3. Metrik font fallback membuat baris kontak sesaat lebih lebar sehingga topbar berayun 89→69→40px. `.topbar` kini `height:40px` + `overflow:hidden`, `.topbar-item` `white-space:nowrap`, `.topbar-desc` disembunyikan ≤1440px (isinya sudah jadi `h1` header), dan item surel disembunyikan ≤1180px (masih ada di footer dan halaman Kontak).
  4. `@keyframes heroGoldSweep` menganimasikan properti `left` dari `-24%` ke `118%`, memaksa layout tiap frame dan menghasilkan ratusan geseran kecil sepanjang 1,2 detik. Sekarang lewat `transform: translateX()` (645% lebar elemen, setara jarak yang sama) sehingga hanya compositor yang bekerja. **Skew `-16deg` wajib ikut dinyatakan di tiap keyframe**, kalau tidak transform dasarnya tertimpa dan pita emasnya jadi tegak.
  Verifikasi: CLS 0,0077 (1920), 0,0202 (1366), 0,0000 (390) — semua jauh di bawah ambang 0,1, tanpa kliping dan overflow horizontal 0.
- **Bobot beranda 25 Jul 2026: 5.879 KB → 2.183 KB (−63%).** Empat PNG di `images/layanan/` dikirim 21–31× lebih besar dari ukuran tampilnya (52×52) dan dua logo brand dipakai sebagai ikon tab 26×26: `logo-eberpadu` 1.342 KB → 3 KB, `logo-ecourt` 682 KB → 7 KB, `logo-direktori-putusan` 542 KB → 2 KB, `logo-badilum` 896 KB → WebP, `logo-ma` 740 KB → WebP, `maklumat-layanan-informasi-publik` 975 KB → 158 KB (dimensi asli dipertahankan karena target zoom). Dua hotlink ke server pihak ketiga di-host sendiri: `perkusi2.png` (sudah 404, ikon rusak di beranda) dan `sisuper.png` (masih hidup tapi rapuh sama). PNG lama tetap di disk sebagai cadangan; rujukan diganti lewat migrasi `20260822_optimize_service_logo_assets.sql` dan `20260824_optimize_brand_logo_assets.sql`. Gambar rusak di beranda: 1 → 0.
- **Peta beranda 25 Jul 2026: diperbaiki, plus kelas bug-nya ditutup.** Modul **810** menyimpan `data-data-data-data-data-data-src` sehingga pemuat lazy `.home-map-card iframe[data-src]` tidak pernah menemukannya dan kartu merender kotak kosong 477px. Penyebabnya `20260731_lazy_home_map.sql` dan `20260801_lazy_home_map_canonical.sql` memakai `REPLACE(content,'src="…"','data-src="…"')`, dan karena hasilnya masih mengandung pola pencarian, setiap pemutaran ulang menambah satu prefiks. Diperbaiki oleh `20260823_repair_lazy_map_attribute.sql` (idempoten, memakai `REGEXP_REPLACE`). Pemindai statis `tools/test_migration_idempotency.py` kini menolak migrasi baru yang memakai `REPLACE(kolom,'A','B')` dengan `A` substring dari `B`; tiga berkas warisan terdaftar sebagai pengecualian yang sudah diperbaiki.
- **Outline heading 25 Jul 2026:** beranda tidak lagi punya heading duplikat. `Berita`/`Pengumuman` di `Kabar Instansi Peradilan` muncul 3× masing-masing; kini nama instansi ikut ke nama aksesibel lewat `<span class="visually-hidden">` tanpa mengubah label visual. Heading `Accessibility Options` milik `media/vendor/accessibility` terbaca `XAccessibility Options♲` karena tombol Close/Reset dirender sebagai `<i>` di dalamnya; `setupAccessPanelSemantics()` di `template.js` memasang `aria-label` bersih pada heading dan menjadikan kedua `<i>` tombol yang bisa dijangkau keyboard. Berkas vendor tidak disentuh.

## Pembaruan 12 Jul 2026: Berita, Pengumuman, dan Transparansi

- Portal artikel **53** dirender khusus di `/berita-dan-pengumuman`; artikel landing lama **6** unpublished dan artikel 53 dipindahkan ke kategori netral agar tidak masuk pagination Berita.
- Kanal kategori memiliki dua entry point yang tetap aktif: `/berita` (menu 141) dan `/berita-dan-pengumuman/berita` (233); `/pengumuman` (142) dan `/berita-dan-pengumuman/pengumuman` (234). Keempat menu wajib memakai `num_leading_articles=0`, `num_intro_articles=6`, `num_columns=3`, `num_links=0`, `orderby_sec=rdate`, `order_date=published`.
- Data lama kategori Berita **12** dan Pengumuman **13** dinormalisasi: `publish_up <= 2000-01-02` diganti `created`. Karena itu category listing, portal, dan hero menggunakan tanggal publikasi terbaru-ke-terlama tanpa sentinel Joomla.
- Menambah article ke kategori 12/13 otomatis memperbarui kanal, portal, dan hero homepage. Gambar memilih `image_fulltext` non-empty lalu `image_intro`; Pengumuman tanpa gambar memakai `images/brand/pengumuman-resmi-pn-natuna.webp`.
- Pagination Berita/Pengumuman berisi tepat enam card per halaman desktop, tiga kolom × dua baris; mobile menjadi compact list. Jangan menyembunyikan `link_items` lewat CSS. State DB dikelola oleh registry `database/migrations/`.
- Keluarga Transparansi memakai renderer terpusat `html/com_content/article/transparency-family.php` untuk artikel 45 dan child 37, 38, 39, 40, 86, 41, 42, 43, 85, 87, 88, 115, 116. Renderer mempertahankan seluruh link arsip, menghapus shell DB duplikat, memberi satu hero h1, breadcrumb, dan navigasi empat kelompok.
- Kelompok Transparansi: **Akuntabilitas Kinerja**, **Keuangan**, **Survei dan Integritas**, **Informasi Publik**. Desktop memakai grouped dropdown/disclosure; mobile accordion hanya membuka kelompok aktif. Semua route lama dipertahankan.
- Focused contracts: `tools/test_news_portal_renderer.php`, `tools/test_news_portal_renderer.py`, `tools/test_news_category_channels.php`, dan `tools/test_transparency_family_renderer.php`.

## Pembaruan 16 Jul 2026: Pengumuman, fasilitas, dan feed

- Blok homepage **Berita Terbaru** diganti **Pengumuman Baru**: satu feature + dua compact, otomatis kategori Pengumuman **13**, gambar `image_fulltext → image_intro → fallback`, route arsip `/pengumuman`, desktop 60:40 dan mobile satu kolom. Hero slider tetap intro-first dan tidak berubah.
- Dua pengumuman BMN resmi diimpor tanpa duplikasi: **Pengumuman Lelang BMN** (4 Juni 2026, artikel 209) dan **Penetapan Pemenang Lelang BMN** (11 Juni 2026, artikel 208). Halaman pertama PDF menjadi dua WebP berbeda di `images/pengumuman/`. Identitas direkonsiliasi berdasarkan URL Drive, alias, dan judul ternormalisasi; duplikat tambahan dipindah ke trash oleh migrasi `20260721_reconcile_bmn_announcements.sql`.
- Galeri Fasilitas modul **480** memakai foto PTSP baru `images/layanan/gallery/ruang-ptsp-2026.webp` (1600×900, WebP sinematik). Tiga halaman detail memakai panel dokumenter lightbox: PTSP 380/230px `contain`, Disabilitas 350/220px `contain`, Posbakum 360/220px `contain` (desktop/mobile). Galeri homepage tetap empat kartu dan ukuran thumbnail lama.
- Migrasi fasilitas berurutan: `20260716_public_facility_documentary_photos.sql` menambah konten/foto; `20260720_facility_panel_size_variants.sql` menambah class varian setelahnya. Jangan mengubah urutan atau mengedit migrasi yang sudah tercatat.
- Focused contracts baru: `tools/test_latest_announcements_showcase.php`, `tools/test_public_facility_photos.php`, dan `tools/test_bmn_announcements.php`.

Spesifikasi serta rencana desain aktif tersedia di [`docs/superpowers/specs/`](docs/superpowers/specs/) dan [`docs/superpowers/plans/`](docs/superpowers/plans/).

## Registry route dan data

| Route/keluarga | Artikel/menu |
|---|---|
| `/transparansi` | artikel **45**; artikel 8 tidak dirender |
| Transparansi children | artikel 37, 38, 39, 40, 86, 41, 42, 43, 85, 87, 88, 115, 116; parent menu 108 |
| Layanan Publik | artikel 26, 11, 12, 13, 14, 15, 19, 97; artikel 13 = Maklumat |
| Profil Hakim / Kepaniteraan / Kesekretariatan | artikel 58 / 59 / 60 |
| Unit kepaniteraan | artikel 107–110; route nested `/profil-pengadilan/profil-kepaniteraan/...` |
| Profil PPPK | artikel 114; menu 278 |
| Sejarah | artikel 54 |

## Registry modul

| ID | Isi/status |
|---|---|
| 482 | Role Model, poster |
| 483 | Instagram, slider sembilan post; update manual |
| 808 | Maklumat duo panel (`home-alerts`) |
| 816 | Kinerja & Akuntabilitas: SKM/IPAK manual per triwulan dan widget DIPA; `tools/refresh-dipa.py` hanya mengganti `.dipa-widget` |
| 817 | DIPA lama, unpublished; jangan dipakai |
| 112 | Quick links |

## File dan operasi penting

- `templates/pn_natuna_2026/index.php` — shell, metadata, menu dan bottom bar homepage.
- `templates/pn_natuna_2026/css/template.css` — styling; aturan mobile utama berada pada blok akhir `@media (max-width:760px)`.
- `templates/pn_natuna_2026/template.js` — interaksi menu, carousel, lightbox, share, sticky navigation.
- `templates/pn_natuna_2026/hero-slider.php` — hero serta renderer showcase Pengumuman Baru.
- `templates/pn_natuna_2026/instansi-feed.php` — feed MA/Badilum/PT Kepri, normalisasi judul, live/fallback status, dan cache.
- `templates/pn_natuna_2026/sipp-schedule.php`, `stats-counter.php`, `html/layouts/chromes/card.php`.
- `tools/refresh-survey.py`, `tools/refresh-dipa.py`, `cron-refresh-instansi.php`.
- [`CRON-AUTOUPDATE-HANDOFF.md`](CRON-AUTOUPDATE-HANDOFF.md) — cron feed, survei, dan DIPA.

Feed Badilum dan PT Kepri diperbarui live oleh cron. MA RI tidak di-fetch langsung karena Cloudflare; berita memakai Google News RSS terfilter + fallback terkurasi, sedangkan pengumuman kembali ke fallback bila RSS tidak memiliki minimal dua item dalam 60 hari. Gunakan `_status` cache/log cron sebagai bukti sumber; jangan mengklaim semua item MA live.

## AMPUH 2026

- Route aktif: `/ampuh`. Deployment rebuild pertama dilakukan sebagai staging terisolasi di `new.pn-natuna.go.id`; setelah QA, staging dapat menggantikan `pn-natuna.go.id` melalui cutover dengan rollback. Manual kanonis: [`CPANEL-STAGING-CUTOVER-RUNBOOK.md`](CPANEL-STAGING-CUTOVER-RUNBOOK.md).
- Dataset deploy kanonis yang dilacak Git: `templates/pn_natuna_2026/data/ampuh-2026.json`; importer: `tools/import-ampuh-checklist.py`; map URL checklist terverifikasi: `tools/ampuh-2026-checklist-links.json`; override sumber/provenance: `tools/ampuh-2026-overrides.json`.
- Workbook otoritatif adalah input operasional privat yang diperoleh dari pemilik data PN Natuna. Jangan commit atau deploy workbook karena berisi catatan internal/tindak lanjut yang tidak termasuk dataset publik. Regenerasi dari salinan privat: `python tools/import-ampuh-checklist.py "<PATH_TO_PRIVATE_WORKBOOK.xlsx>" --output templates/pn_natuna_2026/data/ampuh-2026.json`.
- Folder Drive utama dan seluruh 82 checklist memakai URL viewer publik; hanya sub-checklist 78.3 memiliki URL viewer sendiri. Sub-checklist tanpa URL sengaja tidak menampilkan placeholder/link; renderer tidak boleh meminjam URL checklist sebagai fallback.
- UI final memakai institutional command header, indeks koleksi, search sticky, highlight hasil, rail GOBI desktop dengan panah 44×44, select GOBI mobile, disclosure CSS/motion 180–220 ms, dan reduced-motion guard. Saat URL/data berubah, jalankan `python tools/test_import_ampuh_checklist.py`, `python tools/test_ampuh_directory_interactions.py`, `python tools/test_ampuh_directory_e2e.py`, dan `C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php`.
- Jangan masukkan kredensial, token, atau tautan edit Google Drive ke repo, dataset, override, atau handoff.

## Keamanan dan produksi

Sebelum deploy/go-live, baca dan tuntaskan:

1. [`SECURITY-DEPLOYMENT-HANDOFF.md`](SECURITY-DEPLOYMENT-HANDOFF.md) — preflight, Cloudflare/cPanel, MFA, WAF, origin lock, sesi/token, break-glass, rollback, dan verifikasi.
2. [`SECURITY-BACKUP-MONITORING-RUNBOOK.md`](SECURITY-BACKUP-MONITORING-RUNBOOK.md) — cron privat, backup DB, retensi, alert, respons judol, dan restore clean-room.
3. [`CPANEL-STAGING-CUTOVER-RUNBOOK.md`](CPANEL-STAGING-CUTOVER-RUNBOOK.md) — instalasi pertama `new.pn-natuna.go.id`, update via private checkout GitHub dan `git pull --ff-only`, clone DB/config staging, QA, cutover domain utama, dan rollback.

Selama `new.pn-natuna.go.id` masih disposable staging tanpa konten server penting, full file+DB refresh dapat dijalankan dengan `tools/deploy-cpanel.py --reset-database`; target wajib memiliki marker `.pn-natuna-staging`, nama DB wajib mengandung `staging`, dan dump/credential tetap privat. Hentikan full DB reset setelah go-live.

Instruksi dashboard bukan bukti kontrol sudah aktif. Catat bukti dan tanggal pengujian. Jangan hapus runbook atau tool keamanan dari repo/deployment source; deployment allowlist yang mencegahnya masuk `public_html`.

## Pembaruan 20 Jul 2026: profil, performa, feed, dan kesiapan produksi

- Profil Kesekretariatan kini memiliki submenu alfabetis **Kepegawaian, Organisasi, dan Tata Laksana**, **Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP)**, serta **Umum dan Keuangan**. Ketiganya memakai format unit Kepaniteraan: ringkasan, kartu penanggung jawab/staf, dan tiga butir ruang lingkup layanan. Detail kategori dan navigasi artikel Joomla disembunyikan pada seluruh keluarga Profil Pengadilan.
- Homepage tidak melakukan fetch eksternal server-side. Jadwal SIPP membaca `cache/pn_natuna_sipp_schedule.json` dan diperbarui lewat `tools/cron-refresh-sipp.php`; feed instansi membaca cache lokal dan diperbarui lewat cron privat. Prefetch Beranda aktif saat idle pada koneksi layak; peta dimuat lazy; conservative cache, gzip, dan panduan OPcache produksi sudah disiapkan.
- Feed Mahkamah Agung mencoba endpoint JSON resmi `POST /id/berita` (`cat_id=1`) dan `POST /id/pengumuman` (`cat_id=2`) sebelum Google News/fallback. Browser berhasil menerima data resmi, tetapi PHP lokal menerima Cloudflare challenge. Uji cPanel wajib; status sukses adalah `live-official-json`, sedangkan `official-cloudflare-challenge` berarti perlu jalur resmi/alternatif tanpa mengakali challenge.
- Tab **PT Kepri** di homepage kini berlabel lengkap **Pengadilan Tinggi Kepulauan Riau**.
- Instagram homepage memakai embed profil resmi `https://www.instagram.com/pn.natuna/embed/`, tanpa RSS.app dan tanpa token. Instagram menentukan enam post yang ditampilkan; jumlah itu tidak dapat dipaksa menjadi sembilan. Embed memakai tinggi 350px agar grid utuh pada desktop/mobile.
- Pembaca suara aksesibilitas default aktif kecuali pengguna pernah mematikannya. Locale utterance selalu `id-ID`; suara Indonesia online diprioritaskan, termasuk identitas Microsoft Gadis/Andika yang kadang hanya tersedia di `voiceURI` Edge. Entri bernama `undefined` tanpa identitas Gadis/Andika ditolak.
- Kredensial administrator lokal pernah disebut dalam chat dan hanya untuk pengujian lokal. Jangan deploy akun lokal. Produksi wajib memakai akun bernama per operator, password acak baru, privilege minimum, dan MFA.
- Pekerjaan produksi yang belum dapat dibuktikan dari workstation: QA staging penuh, WAF/rate limit, proteksi `/administrator`, PHP `display_errors=Off`, header keamanan/CSP Report-Only, backup teruji, cutover, dan rollback. Ikuti `SECURITY-DEPLOYMENT-HANDOFF.md` serta `CPANEL-STAGING-CUTOVER-RUNBOOK.md`; jangan langsung overwrite produksi lama.

## Menjalankan di device lain

1. Clone repo dan checkout branch kerja.
2. Restore dump hanya melalui `python tools/restore-local-db.py <path-dump.sql> --mysql <path-mysql>`; perintah ini mengimpor dump lalu mengulang migrasi kanonis dari `database/migrations/`.
3. Jika database sudah terimpor, jalankan `python tools/apply-db-migrations.py --mysql <path-mysql> --reapply`. Jangan menjalankan file SQL delta satu per satu.
4. Sesuaikan `configuration.php` untuk path, DB, host, kredensial, dan `live_site` lokal; file ini tidak dilacak Git.
5. Dari root Joomla jalankan `php -S 127.0.0.1:8080`.
6. Jalankan `php tools/test_homepage_modules.php`, lalu verifikasi `/`, `/transparansi`, `/profil-pengadilan`, artikel Berita/Pengumuman, desktop `≥761px`, dan mobile `≤760px`.

## Invariant dan risiko terbuka

- Jangan mengubah artikel 8 untuk `/transparansi`; route aktif memakai artikel 45.
- Bottom bar hanya homepage; modal semantics drawer hanya saat drawer mobile terbuka.
- Jangan menambah `backdrop-filter` di atas layer animasi Ken Burns.
- `moduleclass_sfx` diperlukan untuk menarget mod_custom; `:has()` langsung tidak cocok karena wrapper `div.custom`.
- Gunakan viewport CDP nyata untuk QA mobile; `--window-size=390` pernah memberi hasil menyesatkan.
- `stats-counter.php` masih memiliki `base_offset` 24.500 yang tidak berbasis data; hapus sebelum produksi.
- NIP Ardiansyah pada artikel 114 berjumlah 19 digit dan perlu konfirmasi kepegawaian.
- Foto gedung sumber hanya 700×523; ganti dengan foto HD landscape minimal 1920px saat tersedia.
- Tanggal artikel lama: gunakan `publish_up` hanya bila lebih baru dari `2000-01-02 00:00:00`, selain itu gunakan `created`.
- Referensi gambar WebP dimigrasikan di DB (`pnn_content` 105/13, `pnn_modules` 808/482); file JPG/PNG lama sudah dihapus dari `images/`. Jangan mengembalikan referensi lama.
- Rule mobile lama `.home-juknis-main > :nth-child(n+8) { contain-intrinsic-size:520px }` berbahaya untuk elemen kosong baru; divider dikecualikan via override di blok EDITORIAL.
- JSON `images` Joomla dapat berisi `image_fulltext` kosong; fallback harus berdasarkan nilai non-kosong ke `image_intro`, dengan path lokal root-relative.

## Prinsip pemeliharaan

- Joomla-native bila cukup; custom code hanya untuk kebutuhan yang tidak dipenuhi Joomla.
- Jangan commit cache, log, runtime, kredensial, token, atau `configuration.php`.
- Backup DB sebelum migrasi struktur/konten.
- Setiap perubahan data Joomla yang wajib mengikuti kode harus berupa migrasi SQL idempoten baru di `database/migrations/`; file yang sudah tercatat tidak boleh diedit.
- Status saat ini ada di dokumen ini; fakta kronologis dan keputusan yang dibatalkan ada di arsip, bukan instruksi aktif.
