# HANDOFF — Rebuild Website PN Natuna

Knowledge base status aktif untuk rebuild Joomla Pengadilan Negeri Natuna Kelas II. Kronologi Juli 2026 dipindahkan ke [`docs/archive/HANDOFF-history-2026-07.md`](docs/archive/HANDOFF-history-2026-07.md).

## Lingkungan kerja

- Repo: <https://github.com/sapyong13-design/web.pn-natuna.git>
- Branch kerja: `continue-joomla-rebuild-polish`
- Root lokal: `C:\tmp\web.pn-natuna`
- URL lokal: `http://localhost:8080`; port harus sama dengan `live_site` di `configuration.php` agar SEF tidak redirect-loop.
- Database lokal: `pn_natuna_rebuild`
- Snapshot penuh terbaru: `database/pn_natuna_rebuild_20260711_instagram_mobile.sql` (lokal, tidak dilacak Git; sebelumnya `..._ui_polish.sql`)
- Stack lokal: PHP 8.3.30 (`C:\laragon\bin\php`), MySQL 8.4.3 (`C:\laragon\bin\mysql`)

Konten artikel dan modul hidup di DB. Setiap perubahan DB yang wajib mengikuti kode harus berupa migrasi SQL idempoten baru di `database/migrations/`; restore dump wajib melalui `tools/restore-local-db.py` agar seluruh migrasi diputar ulang.

## Status antarmuka saat ini

- Hero beranda: sinematik full-bleed memakai `images/hero/gedung-pn-natuna-2026.webp`, dua layer cover/contain dan mask feather; slide pertama `fetchpriority=high`, tidak lazy.
- Role Model, modul **482**: dua poster dari `images/role-model/` (Joko Ciptanto kini `joko-ciptanto-role-model-2026.webp`).
- Instagram: feed otomatis dari RSS.app melalui `tools/cron-refresh-instagram.php` (cron per jam, URL feed di private env), cache JSON + thumbnail WebP lokal di `cache/`+`media/instagram/` (ter-ignore); homepage merender carousel satu kartu 5 detik dari cache; modul **483** iframe manual hanya fallback saat cache tidak valid.
- Kabar Instansi: judul PT Kepri dipulihkan dari slug bila source terpotong; Badilum selalu `logo-badilum.png`; regression test `tools/test-instansi-feed.php`.
- Mode gelap: default terang, aktif hanya melalui tombol dan `localStorage` key `pnNatunaDark`; tidak mengikuti preferensi sistem.
- Mobile `≤760px`: header kompak/sticky, drawer bertingkat, bottom bar lima aksi hanya di homepage, sidebar menjadi snap rail.
- Detail artikel kategori Berita/Pengumuman memakai override `templates/pn_natuna_2026/html/com_content/article/default.php`; kategori lain memakai template core secara langsung.
- Transparansi dan keluarga Profil Pengadilan sudah memakai route canonical, shell terakses, state fokus/gelap/reduced-motion, dan konten DB terbaru.
- Editorial 2026-07-11: section homepage memakai pola `.section-kicker → h2 → .section-desc → konten → satu aksi` (7 section), nav desktop menandai route aktif via `li.active/.current` underline gold, 3 divider statis `.home-section-divider`, board Jadwal/Instansi berlatar `--color-soft`. Blok CSS: `/* EDITORIAL 2026-07-11 */` dan `/* PERF-MOTION 2026-07-11 */` di akhir `template.css`.
- Performa: token shadow `--shadow-subtle/card/overlay`, reveal one-shot maks 10px/380ms (opacity+transform saja), `content-visibility:auto` pada 4 section bawah fold, gambar berat dikonversi WebP (berita pelantikan 157KB, maklumat 332KB, role model 65KB; original di `images/_originals/`, gitignored).
- Maklumat modul **808**: judul seksi hanya dari chrome Joomla; isi modul berisi dua dokumen utuh berdampingan dan lightbox. Desktop horizontal; mobile tetap dua kolom tanpa overflow.

## Pembaruan 12 Jul 2026: Berita, Pengumuman, dan Transparansi

- Portal artikel **53** dirender khusus di `/berita-dan-pengumuman`; artikel landing lama **6** unpublished dan artikel 53 dipindahkan ke kategori netral agar tidak masuk pagination Berita.
- Kanal kategori memiliki dua entry point yang tetap aktif: `/berita` (menu 141) dan `/berita-dan-pengumuman/berita` (233); `/pengumuman` (142) dan `/berita-dan-pengumuman/pengumuman` (234). Keempat menu wajib memakai `num_leading_articles=0`, `num_intro_articles=6`, `num_columns=3`, `num_links=0`, `orderby_sec=rdate`, `order_date=published`.
- Data lama kategori Berita **12** dan Pengumuman **13** dinormalisasi: `publish_up <= 2000-01-02` diganti `created`. Karena itu category listing, portal, dan hero menggunakan tanggal publikasi terbaru-ke-terlama tanpa sentinel Joomla.
- Menambah article ke kategori 12/13 otomatis memperbarui kanal, portal, dan hero homepage. Gambar memilih `image_fulltext` non-empty lalu `image_intro`; Pengumuman tanpa gambar memakai `images/brand/pengumuman-resmi-pn-natuna.webp`.
- Pagination Berita/Pengumuman berisi tepat enam card per halaman desktop, tiga kolom × dua baris; mobile menjadi compact list. Jangan menyembunyikan `link_items` lewat CSS. State DB dikelola oleh registry `database/migrations/`.
- Keluarga Transparansi memakai renderer terpusat `html/com_content/article/transparency-family.php` untuk artikel 45 dan child 37, 38, 39, 40, 86, 41, 42, 43, 85, 87, 88, 115, 116. Renderer mempertahankan seluruh link arsip, menghapus shell DB duplikat, memberi satu hero h1, breadcrumb, dan navigasi empat kelompok.
- Kelompok Transparansi: **Akuntabilitas Kinerja**, **Keuangan**, **Survei dan Integritas**, **Informasi Publik**. Desktop memakai grouped dropdown/disclosure; mobile accordion hanya membuka kelompok aktif. Semua route lama dipertahankan.
- Focused contracts: `tools/test_news_portal_renderer.php`, `tools/test_news_portal_renderer.py`, `tools/test_news_category_channels.php`, dan `tools/test_transparency_family_renderer.php`.

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
- `templates/pn_natuna_2026/hero-slider.php` — hero dan berita terbaru.
- `templates/pn_natuna_2026/instansi-feed.php` — feed MA/Badilum/PT Kepri dan cache.
- `templates/pn_natuna_2026/sipp-schedule.php`, `stats-counter.php`, `html/layouts/chromes/card.php`.
- `tools/refresh-survey.py`, `tools/refresh-dipa.py`, `cron-refresh-instansi.php`.
- [`CRON-AUTOUPDATE-HANDOFF.md`](CRON-AUTOUPDATE-HANDOFF.md) — cron feed, survei, dan DIPA.

Feed Badilum dan PT Kepri diperbarui live oleh cron. MA RI terhalang Cloudflare untuk fetch server-side dan memakai fallback yang diperbarui manual/semi-otomatis. Jangan mengklaim feed MA live.

## AMPUH 2026

- Route aktif: `/ampuh`; target produksi: `ampuh.pn-natuna.go.id`.
- Dataset deploy kanonis yang dilacak Git: `templates/pn_natuna_2026/data/ampuh-2026.json`; importer: `tools/import-ampuh-checklist.py`; map URL checklist terverifikasi: `tools/ampuh-2026-checklist-links.json`; override sumber/provenance: `tools/ampuh-2026-overrides.json`.
- Workbook otoritatif adalah input operasional privat yang diperoleh dari pemilik data PN Natuna. Jangan commit atau deploy workbook karena berisi catatan internal/tindak lanjut yang tidak termasuk dataset publik. Regenerasi dari salinan privat: `python tools/import-ampuh-checklist.py "<PATH_TO_PRIVATE_WORKBOOK.xlsx>" --output templates/pn_natuna_2026/data/ampuh-2026.json`.
- Folder Drive utama dan seluruh 82 checklist memakai URL viewer publik; hanya sub-checklist 78.3 memiliki URL viewer sendiri. Sub-checklist lain sengaja tampil sebagai `Tautan belum tersedia`; renderer tidak boleh meminjam URL checklist sebagai fallback.
- Saat URL baru tersedia, tambahkan hanya URL viewer publik pada dataset atau `tools/ampuh-2026-overrides.json`, regenerasi bila override berubah, lalu jalankan ulang `python tools/test_import_ampuh_checklist.py`, `python tools/test_ampuh_directory_interactions.py`, `python tools/test_ampuh_directory_e2e.py`, dan `C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php`.
- Jangan masukkan kredensial, token, atau tautan edit Google Drive ke repo, dataset, override, atau handoff.

## Keamanan dan produksi

Sebelum deploy/go-live, baca dan tuntaskan:

1. [`SECURITY-DEPLOYMENT-HANDOFF.md`](SECURITY-DEPLOYMENT-HANDOFF.md) — preflight, Cloudflare/cPanel, MFA, WAF, origin lock, sesi/token, break-glass, rollback, dan verifikasi.
2. [`SECURITY-BACKUP-MONITORING-RUNBOOK.md`](SECURITY-BACKUP-MONITORING-RUNBOOK.md) — cron privat, backup DB, retensi, alert, respons judol, dan restore clean-room.

Instruksi dashboard bukan bukti kontrol sudah aktif. Catat bukti dan tanggal pengujian. Jangan hapus runbook atau tool keamanan dari repo/deployment source; deployment allowlist yang mencegahnya masuk `public_html`.

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
