# Editorial Article Override Design

Date: 2026-07-11
Status: Approved direction, pending written-spec review
Scope: Joomla article details in categories `berita` and `pengumuman`

## Goal

Make every current and future news or announcement article look professionally designed without editors building layouts manually or installing a page builder.

## Architecture

Create a Joomla-native `com_content` article override. The override resolves the current category plus its ancestor path, then selects a channel:

- Category `berita` or any descendant category under `berita`: official editorial-news layout.
- Category `pengumuman` or any descendant category under `pengumuman`: formal announcement/document layout.
- Every other category: immediately `require JPATH_BASE . '/components/com_content/tmpl/article/default.php'; return;` from the override. This executes the installed Joomla core template in the same `$this` scope, preserves byte-equivalent core behavior, and avoids maintaining a cloned fallback that drifts after Joomla updates.

No plugin, page builder, JavaScript framework, or database content migration is required. Editors continue using standard Joomla fields.

## Editor Contract

Required for news:

1. Title.
2. Category `Berita`.
3. Intro image or full article image, preferably at least 1200×675.
4. Descriptive image alt text.
5. Opening paragraph of 25–45 words.
6. Article body.

Required for announcements:

1. Title.
2. Category `Pengumuman`.
3. Article body.
4. Any PDF/document links entered in article content or Joomla article links.

Optional fields:

- Image caption.
- Author.
- Tags.
- Modified date.
- Additional images in body.
- Joomla article links and attachments.

Empty optional fields must collapse cleanly. Missing news image uses a branded PN Natuna fallback surface rather than a broken image or an invented stock photo.

## News Detail Layout

### Header

- Breadcrumb-like back link: `Berita PN Natuna` linking to `/berita`.
- Kicker: `Berita PN Natuna`.
- Article title in Fraunces, max readable width around 20ch.
- Metadata row in Indonesian: publish date, category, estimated reading time.
- Joomla edit control remains available to authorized users.
- Unpublished, scheduled, and expired states remain visible to authorized users.

### Hero

- Use `image_fulltext` first, then `image_intro`.
- Responsive 16:9 presentation with controlled max height and `object-fit: cover`.
- Preserve escaped alt text.
- Render optional caption below image.
- Declare dimensions/aspect ratio to reduce layout shift.
- Missing image renders a branded editorial fallback containing PN Natuna identity and category, not decorative stock media.

### Body

- Maximum reading width 68–72ch.
- First substantial paragraph becomes lead text through CSS, not content mutation.
- Body font minimum 16px, line-height around 1.75.
- Consistent styles for h2/h3, lists, blockquotes, links, tables, figures, captions, and inline images.
- Body images remain responsive and retain their source proportions.
- Wide tables receive a local horizontal scroll wrapper or CSS overflow containment without widening the page.
- Joomla content plugin events and pagination remain functional.

### Utilities

- Share row: native Web Share API when available; fallback links for WhatsApp and copy-link action. No third-party SDK.
- Publication note: published date, optional modified date, and institutional publisher.
- Back-to-news action.

### Related News

- Show up to three latest published articles from the same category.
- Exclude current article.
- Respect access level, language, publish-up, and publish-down.
- Display intro image or branded fallback, date, and title.
- Query once per request; no N+1 queries.
- Hide section when no related articles exist.

## Announcement Detail Layout

Announcements share typography and metadata but use a document-first hierarchy:

- Kicker: `Pengumuman Resmi`.
- Formal title and publish date.
- Compact institutional mark instead of a mandatory photographic hero.
- Existing full image may render as a document preview when supplied.
- Article links/PDFs receive clear `Buka dokumen` or `Unduh lampiran` treatment when Joomla provides them.
- Body content and attachment links remain the source of truth.
- Back action points to `/pengumuman`.
- Related section shows up to three other announcements.

## Metadata and Locale

- Dates use Indonesian month names.
- Prefer valid `publish_up`; fall back to `created`.
- Show modified date only when materially later than publish date.
- Estimated reading time uses stripped body word count at 200 words/minute, minimum one minute.
- Semantic `<time datetime>` is required.
- Do not display raw English labels such as `Details`, `Category`, or `Created`.

## Accessibility

- One visible article heading.
- Sequential heading hierarchy inside template chrome.
- Decorative icons use `aria-hidden="true"`; controls keep accessible labels.
- Share/copy feedback uses `aria-live="polite"`.
- Focus-visible treatment meets contrast requirements.
- Hero alt text comes from Joomla image metadata; fallback identity is decorative or labelled once, never duplicated.
- Touch controls are at least 44×44px on mobile.
- Respect `prefers-reduced-motion`.

## Responsive Behavior

- Desktop article header and hero use editorial scale with generous whitespace.
- At ≤760px: single column, reduced title scale, full-width hero, compact metadata wrapping, and share actions that fit without horizontal overflow.
- Tables and long URLs must not widen the viewport.
- Existing fixed mobile controls and footer spacing remain unchanged.
- Dark mode gets explicit surfaces, text, link, metadata, fallback image, and related-card colors.

## SEO and Semantics

- Use `<article>` with `itemscope itemtype="https://schema.org/NewsArticle"` for news and `Article` for announcements.
- Include item properties for headline, datePublished, dateModified, image, and articleBody where available.
- Keep Joomla document title/canonical behavior unchanged.
- Do not inject duplicate global JSON-LD already emitted by the template.

## Files

Expected additions:

- `templates/pn_natuna_2026/html/com_content/article/default.php`: category/ancestor resolver, direct core fallback, and editorial news/announcement implementation.

Expected modifications:

- `templates/pn_natuna_2026/css/template.css`: isolated `.editorial-article-*` styles, responsive and dark variants.
- `templates/pn_natuna_2026/js/template.js`: minimal share/copy enhancement only.
- `HANDOFF.md`: editor contract and override maintenance notes after verification.

## Verification

Test representative routes:

- News with image: `/briefing-pagi-petugas-ptsp-pengadilan-negeri-natuna`.
- News with additional body media if available.
- Announcement with document links: `/pengumuman/pengumuman-seleksi-posbakum-tahun-2026`.
- Direct news/announcement child-category article when present, proving ancestor detection.
- Non-news article `/transparansi` (live article id 45), proving the direct core fallback remains unchanged.

Viewport matrix:

- 320×568.
- 390×844.
- 760px.
- 761px.
- 1280px.
- 1440px.
- Dark mode and reduced motion.

Behavior checks:

- Correct Indonesian dates and reading time.
- Hero image/fallback and alt/caption.
- No raw Joomla metadata labels.
- Plugin event output remains present.
- Authorized edit control remains available where applicable.
- Share opens native API when supported; fallback copy works and reports status.
- Related query excludes current item and respects publication/access/language.
- Announcement attachments remain reachable.
- Non-news article output remains functionally unchanged.
- No horizontal overflow from body images, tables, long links, navbar, or footer.

## Acceptance Criteria

- Existing and future news/announcement details receive the editorial layout automatically.
- Editors need no per-article HTML layout and no plugin.
- Missing optional content produces no blank boxes or broken controls.
- News and announcement variants are visually distinct but share one design system.
- Other Joomla article categories do not inherit the editorial layout.
- Desktop and mobile navigation/footer behavior stays unchanged.
