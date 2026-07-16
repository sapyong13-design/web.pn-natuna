# Latest Announcements Showcase Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace homepage Berita Terbaru grid with an automatic, visually asymmetric showcase for the three latest published announcements.

**Architecture:** Keep existing Joomla article query and formatting helpers in `hero-slider.php`; change only homepage renderer data source and markup. Add announcement-specific CSS instead of overloading legacy news-card rules. Protect server-rendered contract with a focused PHP source/fixture test, then smoke-test rendered Joomla output in real Chromium.

**Tech Stack:** Joomla PHP, MySQL-backed `#__content`, CSS Grid, focused PHP contract tests, Chromium browser verification.

## Global Constraints

- Category source is Pengumuman ID `13`, ordered by valid `publish_up` then `created`, maximum three items.
- Copy is exactly: `Informasi Resmi`, `Pengumuman Baru`, `Informasi, pemberitahuan, dan agenda resmi terbaru Pengadilan Negeri Natuna.`, `Semua Pengumuman`, and `Baca Pengumuman`.
- Main action targets `/pengumuman`.
- Image precedence is non-empty `image_fulltext`, then non-empty `image_intro`, then `/images/brand/pengumuman-resmi-pn-natuna.webp`.
- Desktop above `900px` uses one feature item plus up to two stacked compact items; `900px` and below uses one column.
- Preserve hero slider, portal, category channels, database content, and database schema.
- No carousel, autoplay, modal, filter, manual selection, or database migration.
- Motion changes only `transform`, color, border, and shadow; existing reduced-motion behavior remains authoritative.

---

### Task 1: Renderer contract and announcement markup

**Files:**
- Create: `tools/test_latest_announcements_showcase.php`
- Modify: `templates/pn_natuna_2026/hero-slider.php:79-97,126-162`

**Interfaces:**
- Consumes: `pn_natuna_hero_latest_articles(int $catId, int $limit = 4): array`, `pn_natuna_hero_article_url(object $article, int $catId): string`, `pn_natuna_hero_article_image(object $article): string`, and `pn_natuna_hero_excerpt(?string $introtext, int $length = 90): string`.
- Produces: `pn_natuna_render_latest_announcements(?array $articles = null): void`; null loads category 13, while an injected array supports deterministic renderer contract tests.
- Produces markup classes: `.announcement-showcase`, `.announcement-showcase__grid`, `.announcement-feature`, `.announcement-feature__media`, `.announcement-feature__copy`, `.announcement-feature__cta`, `.announcement-compact-list`, `.announcement-compact`, `.announcement-compact__media`, `.announcement-compact__copy`, and `.announcement-compact__arrow`.

- [ ] **Step 1: Write failing focused contract test**

Create `tools/test_latest_announcements_showcase.php` with Joomla bootstrap matching repository focused renderer tests. Construct three article objects with IDs `901`, `902`, `903`, category `13`, fixed dates, images, aliases, titles, and intro text. Buffer `pn_natuna_render_latest_announcements($fixtures)` and assert:

```php
$expect(function_exists('pn_natuna_render_latest_announcements'), 'Announcement renderer is missing.');
$expect(str_contains($html, 'Informasi Resmi'), 'Showcase kicker is missing.');
$expect(str_contains($html, '<h2>Pengumuman Baru</h2>'), 'Showcase heading is missing.');
$expect(str_contains($html, 'href="/pengumuman"'), 'Archive action must target /pengumuman.');
$expect(substr_count($html, 'class="announcement-feature"') === 1, 'Exactly one feature item is required.');
$expect(substr_count($html, 'class="announcement-compact"') === 2, 'Exactly two compact items are required.');
$expect(str_contains($html, 'Baca Pengumuman'), 'Feature CTA is missing.');
$expect(strpos($html, 'Pengumuman 901') < strpos($html, 'Pengumuman 902'), 'DOM order must preserve newest-first order.');
```

Render fixtures containing zero, one, and two objects as separate buffered calls. Assert zero produces an empty string, one produces one feature and no compact list, and two produce one feature plus one compact item. Read `hero-slider.php` source and assert the renderer default contains `pn_natuna_hero_latest_articles(13, 3)`.

- [ ] **Step 2: Run test and verify RED**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_latest_announcements_showcase.php
```

Expected: FAIL with `Announcement renderer is missing.`

- [ ] **Step 3: Add showcase-specific image precedence helper**

Leave `pn_natuna_hero_article_image()` unchanged because the hero slider depends on its intro-first behavior. Add `pn_natuna_announcement_image(object $article): string` for showcase rendering only. Select non-empty `image_fulltext` first, then non-empty `image_intro`, then the announcement fallback:

```php
$decoded = json_decode((string) ($article->images ?? ''), true) ?: [];
$img = trim((string) ($decoded['image_fulltext'] ?? ''));
if ($img === '') {
    $img = trim((string) ($decoded['image_intro'] ?? ''));
}
if ($img === '') {
    return '/images/brand/pengumuman-resmi-pn-natuna.webp';
}
return '/' . ltrim(explode('#', $img)[0], '/');
```

Add fixture assertions proving the showcase helper prefers fulltext, falls back to intro, and then uses the official announcement asset. Also assert `pn_natuna_hero_article_image()` still prefers intro when both fields exist, protecting hero behavior.

- [ ] **Step 4: Implement minimal announcement renderer**

Replace `pn_natuna_render_latest_news()` with `pn_natuna_render_latest_announcements(?array $articles = null): void`. When `$articles === null`, call `pn_natuna_hero_latest_articles(13, 3)`. Return before output for an empty array. Split first item with `array_shift()` and retain at most two compact items.

Feature markup must include lazy image, `Terbaru` badge, full Indonesian date, escaped title, optional 140-character excerpt, and `Baca Pengumuman`. Compact markup must include lazy thumbnail, date, escaped title, and an `aria-hidden="true"` arrow. Every item URL must call `pn_natuna_hero_article_url($article, 13)`.

- [ ] **Step 5: Update homepage callsite cleanly**

Modify `templates/pn_natuna_2026/index.php:199-200` to check and call only `pn_natuna_render_latest_announcements`. Leave no alias or compatibility wrapper for `pn_natuna_render_latest_news`.

- [ ] **Step 6: Run focused test and verify GREEN**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_latest_announcements_showcase.php
```

Expected: `latest announcements showcase contract: ok`

- [ ] **Step 7: Run syntax checks**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe -l templates/pn_natuna_2026/hero-slider.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe -l templates/pn_natuna_2026/index.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe -l tools/test_latest_announcements_showcase.php
```

Expected: `No syntax errors detected` for all three files.

- [ ] **Step 8: Commit renderer contract**

```bash
git add tools/test_latest_announcements_showcase.php templates/pn_natuna_2026/hero-slider.php templates/pn_natuna_2026/index.php
git commit -m "feat: render latest announcements showcase"
```

---

### Task 2: Responsive announcement presentation

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css:8178-8300`
- Test: `tools/test_latest_announcements_showcase.php`

**Interfaces:**
- Consumes: announcement class contract produced by Task 1.
- Produces: desktop 60:40 grid, stacked compact column, single-item full-width state, responsive one-column layout, visible hover/focus states, dark mode styles, and reduced-motion-safe transitions.

- [ ] **Step 1: Extend contract test with CSS assertions**

Read `templates/pn_natuna_2026/css/template.css` in the focused test and assert presence of:

```php
$expect(str_contains($css, 'grid-template-columns: minmax(0, 3fr) minmax(280px, 2fr)'), 'Desktop showcase grid must use a 60:40 composition.');
$expect(str_contains($css, '@media (max-width: 900px)'), 'Responsive showcase breakpoint is missing.');
$expect(str_contains($css, '.announcement-feature:focus-visible'), 'Feature focus style is missing.');
$expect(str_contains($css, '.announcement-compact:focus-visible'), 'Compact focus style is missing.');
$expect(str_contains($css, 'body.is-dark .announcement-feature'), 'Dark mode feature style is missing.');
```

- [ ] **Step 2: Run test and verify RED**

Run focused test. Expected: FAIL with `Desktop showcase grid must use a 60:40 composition.`

- [ ] **Step 3: Replace legacy homepage news-card CSS**

Remove the `.news-cards-*` and `.news-card*` block used only by the replaced renderer. Add announcement-specific rules:

- `.announcement-showcase__grid`: `display:grid`, `grid-template-columns:minmax(0,3fr) minmax(280px,2fr)`, varied gap.
- `.announcement-feature`: grid container, full border, radius, overflow hidden, tinted neutral background, text-decoration none.
- `.announcement-feature__media`: decisive wide image with stable aspect ratio and overflow hidden.
- `.announcement-feature__copy`: structured spacing for date, display title, excerpt, and CTA.
- `.announcement-compact-list`: two equal rows where two items exist.
- `.announcement-compact`: thumbnail/text/arrow grid with full border and no colored side stripe.
- `:hover` and `:focus-visible`: shared border, shadow, title color, arrow translation, and image scale states.
- `body.is-dark`: readable surfaces, borders, dates, titles, excerpts, and focus ring.
- `.announcement-showcase__grid.is-single` or equivalent one-item selector: one column so feature spans full width.

Use existing color, radius, shadow, and font variables. Do not add gradient text, backdrop filters, or layout-property animation.

- [ ] **Step 4: Add responsive rules**

Inside `@media (max-width: 900px)`, set showcase grid to one column. Keep compact items as thumbnail-left rows. At the existing `max-width:760px` section, constrain thumbnail width, title size, and feature media ratio so 390px viewport has no horizontal overflow.

- [ ] **Step 5: Run focused test and verify GREEN**

Run focused test. Expected: `latest announcements showcase contract: ok`

- [ ] **Step 6: Commit responsive presentation**

```bash
git add templates/pn_natuna_2026/css/template.css tools/test_latest_announcements_showcase.php
git commit -m "style: shape announcement showcase hierarchy"
```

---

### Task 3: End-to-end homepage verification

**Files:**
- Modify only if verification exposes a real defect: `templates/pn_natuna_2026/hero-slider.php`, `templates/pn_natuna_2026/index.php`, `templates/pn_natuna_2026/css/template.css`, `tools/test_latest_announcements_showcase.php`

**Interfaces:**
- Consumes: running Joomla site at `http://localhost:8080` with local database.
- Produces: browser-observed desktop, mobile, keyboard, link, image fallback, and dark-mode evidence.

- [ ] **Step 1: Run focused and existing homepage contracts**

Run:

```bash
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_latest_announcements_showcase.php
C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_homepage_modules.php
```

Expected: both commands end with `: ok`.

- [ ] **Step 2: Smoke-test desktop in Chromium**

Open `http://localhost:8080` at `1366×768`. Verify through DOM and screenshot:

- `h2` text is `Pengumuman Baru`.
- Exactly one `.announcement-feature` and no more than two `.announcement-compact` elements exist.
- Main feature appears left of compact column.
- Main action resolves to `/pengumuman`.
- Each rendered item href resolves to a category 13 announcement article.
- No console-visible PHP error text or broken images appears.

- [ ] **Step 3: Smoke-test keyboard and dark mode**

Tab to feature, compact items, and archive action; capture visible focus state. Toggle existing dark mode control and verify all dates, titles, excerpts, borders, and action labels remain readable.

- [ ] **Step 4: Smoke-test mobile in Chromium**

Set viewport to `390×844`, reload, and verify through DOM bounds and screenshot:

- Feature precedes compact items in one column.
- Compact thumbnails remain left of their copy.
- `document.documentElement.scrollWidth === window.innerWidth`.
- Tap targets remain distinct and readable.

- [ ] **Step 5: Verify live links**

Open first feature link and return; open `/pengumuman` action and confirm channel heading loads. Expected HTTP navigation succeeds without redirect loop.

- [ ] **Step 6: Fix only observed defects and repeat checks**

For each defect, first add or tighten a focused assertion when behavior is automatable, confirm it fails, apply minimal correction, then rerun Tasks 3 Steps 1–5.

- [ ] **Step 7: Commit verification fixes if any**

```bash
git add templates/pn_natuna_2026/hero-slider.php templates/pn_natuna_2026/index.php templates/pn_natuna_2026/css/template.css tools/test_latest_announcements_showcase.php
git commit -m "fix: complete announcement showcase verification"
```

Skip this commit when verification required no code changes.
