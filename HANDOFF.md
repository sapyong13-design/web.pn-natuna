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

Konten artikel dan modul hidup di DB. Setiap perubahan DB harus disertai delta SQL idempotent dan snapshot penuh baru; file `database/_*.sql` adalah delta yang sudah diterapkan.

## Status antarmuka saat ini

- Hero beranda: sinematik full-bleed memakai `images/hero/gedung-pn-natuna-2026.webp`, dua layer cover/contain dan mask feather; slide pertama `fetchpriority=high`, tidak lazy.
- Role Model, modul **482**: dua poster dari `images/role-model/` (Joko Ciptanto kini `joko-ciptanto-role-model-2026.webp`).
- Instagram: feed otomatis dari RSS.app melalui `tools/cron-refresh-instagram.php` setiap jam, cache JSON + WebP lokal; homepage memakai carousel satu kartu 5 detik dan modul **483** iframe manual hanya sebagai fallback.
- Mode gelap: default terang, aktif melalui tombol dan `localStorage` key `pnNatunaDark`; state disinkronkan ke `<html>`/`color-scheme`/theme-color, termasuk surface mobile dan dialog.
- Mobile `≤760px`: header kompak/sticky, drawer bertingkat, bottom bar lima aksi hanya di homepage, sidebar snap rail tanpa nested vertical scroll; `content-visibility` homepage dinonaktifkan pada mobile untuk mencegah layout jump/blinking.
- Detail artikel kategori Berita/Pengumuman memakai override `templates/pn_natuna_2026/html/com_content/article/default.php`; kategori lain memakai template core secara langsung.
- Transparansi dan keluarga Profil Pengadilan sudah memakai route canonical, shell terakses, state fokus/gelap/reduced-motion, dan konten DB terbaru.
- Editorial 2026-07-11: section homepage memakai pola `.section-kicker → h2 → .section-desc → konten → satu aksi` (7 section), nav desktop menandai route aktif via `li.active/.current` underline gold, 3 divider statis `.home-section-divider`, board Jadwal/Instansi berlatar `--color-soft`. Blok CSS: `/* EDITORIAL 2026-07-11 */` dan `/* PERF-MOTION 2026-07-11 */` di akhir `template.css`.
- Performa: token shadow `--shadow-subtle/card/overlay`, reveal one-shot maks 10px/380ms, Instagram memakai WebP lokal tanpa iframe saat cache aktif, gambar berat utama sudah dikonversi WebP; original di `images/_originals/` dan runtime Instagram di `media/instagram/` ter-ignore.
- Maklumat modul **808**: compact document row, satu heading section, dua dokumen utuh tanpa crop, lightbox keyboard-safe; desktop horizontal, mobile responsif.
- Map modul **810** tetap memakai iframe Google Maps `loading="lazy"` seperti state sebelumnya; eksperimen click-to-load sudah direvert sesuai keputusan pemilik.
- Kabar Instansi: judul PT Kepri dipulihkan dari slug bila source terpotong; Badilum selalu memakai `logo-badilum.png`; regression test di `tools/test-instansi-feed.php`.
- Aksesibilitas: search overlay dan Maklumat lightbox trap focus/inert/Escape/focus-return; carousel tersembunyi inert; tab Instansi roving tabindex; sticky nav memakai IntersectionObserver tanpa continuous scroll handler.

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

## Keamanan dan produksi

Sebelum deploy/go-live, baca dan tuntaskan:

1. [`SECURITY-DEPLOYMENT-HANDOFF.md`](SECURITY-DEPLOYMENT-HANDOFF.md) — preflight, Cloudflare/cPanel, MFA, WAF, origin lock, sesi/token, break-glass, rollback, dan verifikasi.
2. [`SECURITY-BACKUP-MONITORING-RUNBOOK.md`](SECURITY-BACKUP-MONITORING-RUNBOOK.md) — cron privat, backup DB, retensi, alert, respons judol, dan restore clean-room.

Instruksi dashboard bukan bukti kontrol sudah aktif. Catat bukti dan tanggal pengujian. Jangan hapus runbook atau tool keamanan dari repo/deployment source; deployment allowlist yang mencegahnya masuk `public_html`.

## Menjalankan di device lain

1. Clone repo dan checkout branch kerja.
2. Import snapshot lokal terbaru yang tercantum pada bagian Lingkungan kerja ke database `pn_natuna_rebuild`.
3. Sesuaikan `configuration.php` untuk path, DB, host, kredensial, dan `live_site` lokal; file ini tidak dilacak Git.
4. Dari root Joomla jalankan `php -S 127.0.0.1:8080`.
5. Verifikasi `/`, `/transparansi`, `/profil-pengadilan`, artikel Berita/Pengumuman, desktop `≥761px`, dan mobile `≤760px`.

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
- Rule legacy mobile `content-visibility/contain-intrinsic-size` dapat menyebabkan scroll blinking; override `MOBILE SCROLL STABILITY 2026-07-11` wajib mempertahankan `content-visibility:visible` pada homepage `≤760px`.
- JSON `images` Joomla dapat berisi `image_fulltext` kosong; fallback harus berdasarkan nilai non-kosong ke `image_intro`, dengan path lokal root-relative.

## Prinsip pemeliharaan

- Joomla-native bila cukup; custom code hanya untuk kebutuhan yang tidak dipenuhi Joomla.
- Jangan commit cache, log, runtime, kredensial, token, atau `configuration.php`.
- Backup DB sebelum migrasi struktur/konten.
- Status saat ini ada di dokumen ini; fakta kronologis dan keputusan yang dibatalkan ada di arsip, bukan instruksi aktif.
