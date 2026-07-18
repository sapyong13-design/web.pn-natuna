# Announcement and YouTube Showcase Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah showcase Pengumuman Baru menjadi section 45:55 berisi satu pengumuman terbaru dan player YouTube lazy-load dengan dua video wajib serta tiga video terbaru otomatis.

**Architecture:** `youtube-feed.php` menjadi boundary data tunggal untuk parsing Atom, deduplikasi, fallback, cache, dan renderer. `cron-refresh-youtube.php` mengambil feed resmi dan mempromosikan cache secara atomik; homepage hanya membaca cache lokal dan tidak menghubungi YouTube saat request. JavaScript template menangani pemilihan video dan membuat iframe `youtube-nocookie.com` hanya setelah klik Putar.

**Tech Stack:** Joomla 5, PHP 8.3, YouTube Atom/oEmbed metadata, JSON cache lokal, vanilla JavaScript, CSS.

## Global Constraints

- Desktop memakai layout 45:55; tablet/mobile menjadi satu kolom.
- Komposisi maksimal lima video: `-Di2t-yUZ1I`, `kQ0dMRp1W_g`, lalu tiga video feed terbaru setelah deduplikasi.
- Homepage tidak melakukan network request ke YouTube dan tidak membuat iframe sebelum klik Putar.
- Embed memakai `youtube-nocookie.com` dan selalu menyediakan link `Tonton di YouTube`.
- Feed gagal tidak boleh menimpa cache valid; tanpa cache, dua video wajib tetap tampil.
- Tidak memakai YouTube Data API atau API key.
- Seluruh rangkaian menjadi satu commit lokal; jangan push tanpa perintah eksplisit.

---

### Task 1: YouTube Feed Domain dan Cache

**Files:**
- Create: `templates/pn_natuna_2026/youtube-feed.php`
- Create: `tools/test-youtube-feed.php`

**Interfaces:**
- Produces: `pn_natuna_youtube_pinned(): array`, `pn_natuna_youtube_parse_atom(string $xml): array`, `pn_natuna_youtube_merge(array $pinned, array $latest, int $limit = 5): array`, `pn_natuna_youtube_load_cache(?string $path = null): array`, dan `pn_natuna_youtube_promote_cache(string $path, array $payload): bool`.
- Item shape: `['id' => string, 'title' => string, 'published' => string, 'url' => string, 'thumbnail' => string, 'source' => 'wajib'|'terbaru']`.

- [ ] **Step 1: Tulis fixture Atom dan kontrak gagal**

`tools/test-youtube-feed.php` harus memeriksa: ID video Atom diekstrak, judul/tanggal/thumbnail dinormalisasi, dua pinned selalu di awal, pinned yang muncul di feed tidak berulang, hasil maksimal lima, JSON invalid kembali ke pinned, dan promosi cache memakai payload valid saja.

- [ ] **Step 2: Jalankan kontrak merah**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test-youtube-feed.php
```

Expected: FAIL karena `youtube-feed.php` atau fungsi domain belum ada.

- [ ] **Step 3: Implementasikan fungsi domain minimal**

Pinned wajib memakai metadata terverifikasi:

```php
[
  'id' => '-Di2t-yUZ1I',
  'title' => 'Video Profile Pengadilan Negeri / Perikanan Ranai',
  'thumbnail' => 'https://i.ytimg.com/vi/-Di2t-yUZ1I/hqdefault.jpg',
  'source' => 'wajib',
]
```

```php
[
  'id' => 'kQ0dMRp1W_g',
  'title' => 'Tata cara penggunaan e-Berpadu',
  'thumbnail' => 'https://i.ytimg.com/vi/kQ0dMRp1W_g/hqdefault.jpg',
  'source' => 'wajib',
]
```

Parser wajib memakai `LIBXML_NONET | LIBXML_NOCDATA`, menerima hanya ID `/^[A-Za-z0-9_-]{11}$/`, dan membentuk URL kanonis `https://www.youtube.com/watch?v={id}`. Merge memakai associative set berdasarkan ID.

- [ ] **Step 4: Jalankan kontrak hijau**

Expected: `youtube feed tests: OK`.

### Task 2: Refresher YouTube yang Aman

**Files:**
- Create: `tools/cron-refresh-youtube.php`
- Modify: `.gitignore`
- Modify: `CRON-AUTOUPDATE-HANDOFF.md`
- Test: `tools/test-youtube-feed.php`

**Interfaces:**
- Consumes: fungsi domain Task 1.
- Produces: `cache/pn_natuna_youtube/feed.json` dengan `updated_at`, `source_updated_at`, dan `items`.

- [ ] **Step 1: Tambahkan kontrak source refresher**

Kontrak memeriksa URL channel ID `UCuPb35OggK2PKdW7Ed0qszA`, HTTPS-only, timeout, batas payload, user agent, cache atomic via `pn_natuna_youtube_promote_cache`, serta pesan kegagalan yang menyatakan cache lama dipertahankan.

- [ ] **Step 2: Jalankan kontrak merah**

Expected: FAIL karena refresher belum ada.

- [ ] **Step 3: Implementasikan CLI refresher**

Alur exact:

1. Tolak non-CLI dengan 404.
2. Fetch `https://www.youtube.com/feeds/videos.xml?channel_id=UCuPb35OggK2PKdW7Ed0qszA` memakai cURL, verifikasi TLS, timeout 15 detik, maksimal 2 MiB.
3. Parse, merge pinned + tiga terbaru, dan tolak hasil di bawah dua pinned.
4. Tulis cache melalui file sementara + `LOCK_EX` + rename.
5. Log ke `logs/youtube-refresh.log`; cache lama tidak disentuh ketika fetch/parse/write gagal.

Tambahkan ignore untuk `/cache/pn_natuna_youtube/` dan `/logs/youtube-refresh.log`. Dokumentasikan command cron PHP per jam di `CRON-AUTOUPDATE-HANDOFF.md`.

- [ ] **Step 4: Jalankan refresher nyata dan kontrak**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/cron-refresh-youtube.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test-youtube-feed.php
```

Expected: refresher exit 0, cache berisi tepat dua `wajib` dan tiga `terbaru` unik, test `OK`.

### Task 3: Renderer Showcase 45:55

**Files:**
- Modify: `templates/pn_natuna_2026/hero-slider.php`
- Modify: `templates/pn_natuna_2026/index.php`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify: `tools/test_latest_announcements_showcase.php`

**Interfaces:**
- Consumes: `pn_natuna_youtube_load_cache()` dan pinned fallback Task 1.
- Produces: `pn_natuna_render_latest_announcements(?array $articles = null, ?array $videos = null): void`.

- [ ] **Step 1: Ubah kontrak showcase menjadi merah**

Kontrak baru harus meminta satu artikel kategori 13, tepat satu `.announcement-feature`, tidak ada `.announcement-compact`, satu `.youtube-showcase-player`, maksimal lima `[data-youtube-item]`, dua label `Wajib`, heading `Pengumuman & Video Terbaru`, link `/pengumuman`, link channel resmi, dan CSS `grid-template-columns: minmax(0, 45fr) minmax(0, 55fr)`.

- [ ] **Step 2: Jalankan kontrak merah**

Expected: kontrak lama gagal pada heading, compact count, video markup, dan komposisi grid.

- [ ] **Step 3: Implementasikan renderer dan include**

`index.php` harus memuat `youtube-feed.php` bersama helper template lain sebelum render. Renderer mengambil `pn_natuna_hero_latest_articles(13, 1)`, memuat cache atau pinned, lalu menghasilkan:

- section heading dan dua link aksi;
- satu pengumuman kiri;
- preview kanan dengan thumbnail, judul, tombol Putar, link fallback YouTube, live region;
- rail lima button dengan `data-video-id`, `data-video-title`, `data-video-thumbnail`, `aria-current`, label sumber.

Hapus markup compact announcement lama, tetapi pertahankan fungsi image/date yang dipakai kartu utama.

- [ ] **Step 4: Implementasikan CSS**

Desktop 45:55, player 16:9, rail lima item, state active/focus/dark. Pada `max-width: 1180px`, satu kolom. Pada `max-width: 760px`, rail horizontal dengan `scroll-snap-type: x mandatory`, item memiliki target sentuh minimal 44px, dan body tidak overflow.

- [ ] **Step 5: Jalankan kontrak hijau**

Expected: `latest announcements showcase contract: ok`.

### Task 4: Lazy Inline Player

**Files:**
- Modify: `templates/pn_natuna_2026/js/template.js`
- Create: `tools/test_youtube_showcase.php`

**Interfaces:**
- Consumes: renderer attributes Task 3.
- Produces: `setupYouTubeShowcase()` yang dipanggil saat `DOMContentLoaded`.

- [ ] **Step 1: Tulis kontrak lazy player merah**

Kontrak source memeriksa: tidak ada iframe pada HTML renderer awal; iframe dibentuk dari `https://www.youtube-nocookie.com/embed/`; video ID divalidasi regex 11 karakter; `title`, `allow`, `allowfullscreen`, dan `referrerpolicy` disetel; rail memperbarui `aria-current`; live region diperbarui; link fallback menuju watch URL kanonis.

- [ ] **Step 2: Jalankan kontrak merah**

Expected: FAIL karena `setupYouTubeShowcase()` belum ada.

- [ ] **Step 3: Implementasikan interaksi minimal**

Klik rail mengganti preview `src`, judul, label tombol, fallback URL, active state, dan live region. Klik Putar membuat satu iframe dan mengganti preview; klik rail setelah player aktif hanya mengganti iframe `src`. Gunakan event listener per section dan jangan memuat YouTube script.

- [ ] **Step 4: Jalankan kontrak hijau dan syntax check**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_youtube_showcase.php
node --check templates/pn_natuna_2026/js/template.js
```

Expected: `youtube showcase contract: ok`, syntax exit 0.

### Task 5: Browser Verification, Handoff, dan Satu Commit

**Files:**
- Modify: `HANDOFF.md`
- Verify all files above.

- [ ] **Step 1: Jalankan kontrak lengkap**

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test-youtube-feed.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_latest_announcements_showcase.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_youtube_showcase.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_homepage_modules.php
node --check templates/pn_natuna_2026/js/template.js
```

Expected: semua exit 0.

- [ ] **Step 2: Smoke test browser desktop**

Pada 1920×1080: pastikan 45:55, satu pengumuman, lima video unik, iframe tidak ada sebelum klik, klik Putar memakai `youtube-nocookie.com`, rail mengganti video, link fallback benar, dan tidak ada layout shift besar.

- [ ] **Step 3: Smoke test browser mobile dan dark mode**

Pada 390×844: section satu kolom, rail snap horizontal, tidak ada overflow body, target sentuh 44px, keyboard focus terlihat, dark mode terbaca, reduced motion tidak menghilangkan fungsi.

- [ ] **Step 4: Perbarui handoff**

Catat channel ID, dua pinned ID/judul, cache path, cron command, renderer, lazy iframe privacy, kontrak test, dan fallback cache.

- [ ] **Step 5: Buat satu commit lokal**

```bash
git add .gitignore CRON-AUTOUPDATE-HANDOFF.md HANDOFF.md cache/.gitkeep templates/pn_natuna_2026 tools docs/superpowers/specs/2026-07-16-announcement-youtube-showcase-design.md docs/superpowers/plans/2026-07-16-announcement-youtube-showcase.md
git commit -m "feat: add announcement and YouTube showcase"
```

Jangan push. Jika cache runtime di-ignore, jangan paksa menambahkannya.