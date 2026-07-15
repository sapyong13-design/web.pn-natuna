# Live Content Import and Transparency Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reproduce all public news/announcement content locally with owned images and unify Transparency navigation with internal page navigation patterns.

**Architecture:** A fixture-tested Python importer crawls listing/detail HTML into a deterministic manifest and local images, then upserts the local Joomla rebuild DB. The centralized Transparency renderer switches from duplicate disclosure trees to one grouped link grid. Local DB is exported as a fresh MariaDB-compatible private dump after verification.

**Tech Stack:** Python 3 standard library, Joomla PHP renderer, MySQL local DB, MariaDB-compatible dump, vanilla CSS, Chromium QA.

## Global Constraints

- Source host must be HTTPS `www.pn-natuna.go.id`.
- Import all discoverable public archive pages.
- Store images locally; no hotlinks.
- Never write directly to staging during import.
- Existing public routes and unmatched local articles remain intact.
- No new runtime dependency.

---

### Task 1: Deterministic public-content crawler

**Files:**
- Create: `tools/import-live-news.py`
- Create: `tools/test_import_live_news.py`
- Create: `tools/fixtures/live-news-listing.html`
- Create: `tools/fixtures/live-news-detail.html`

- [ ] Write fixture tests for pagination discovery, URL deduplication, title/date/body extraction, sanitization, image validation, alias stability, and rejected hosts/schemes.
- [ ] Run test; verify failure because importer does not exist.
- [ ] Implement bounded HTTPS fetch, listing/detail parser, sanitizer, image downloader, and deterministic manifest writer.
- [ ] Run tests; expect importer contract `ok`.

### Task 2: Crawl and review complete public archive

**Files:**
- Create: `tools/live-news-import.json`
- Create: `images/news/imported/*`

- [ ] Discover verified news and announcement URLs from live navigation.
- [ ] Crawl every pagination page and detail URL once.
- [ ] Record counts, rejected records, and image results.
- [ ] Review empty titles, invalid dates, duplicate URLs/aliases, unsafe markup, hotlinks, and missing images.
- [ ] Re-run importer and prove deterministic manifest output.

### Task 3: Upsert local Joomla content

**Files:**
- Modify: `tools/import-live-news.py`
- Create: `database/migrations/20260719_import_live_news.sql`
- Test: `tools/test_import_live_news.py`

- [ ] Add failing tests for category mapping, source-URL identity, stable reruns, preserved unmatched content, Joomla image JSON, and MariaDB-safe SQL.
- [ ] Implement manifest-to-SQL and local-DB upsert using source URL provenance.
- [ ] Apply to local `pn_natuna_rebuild` only.
- [ ] Run import twice and verify counts do not increase.
- [ ] Verify newest-first portal/category queries display imported records.

### Task 4: Unified Transparency navigation

**Files:**
- Modify: `templates/pn_natuna_2026/html/com_content/article/transparency-family.php`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify: `tools/test_transparency_family_renderer.php`

- [ ] Change focused contract to require one grouped semantic nav and reject `<details>`, duplicate desktop/mobile trees, and old selectors.
- [ ] Run renderer test; verify expected failure.
- [ ] Render one `.transparency-family__nav` grid with group headings, exact routes, and `aria-current`.
- [ ] Style four/two/one-column responsive navigation using internal-nav tokens, active maroon fill, dark mode, focus, 44 px targets, and wrapping.
- [ ] Run renderer contract; expect `ok`.

### Task 5: Local and browser verification

- [ ] Run importer, news portal, news category, transparency renderer, and migration tests.
- [ ] Verify local homepage, news, announcement, all Transparency routes, and representative imported articles.
- [ ] Browser QA light/dark, keyboard, reduced motion, and widths 320/390/760/761/1280/1440.
- [ ] Confirm no legacy hotlinks, unsafe embedded markup, duplicates, console errors, or horizontal overflow.

### Task 6: Export deployable staging state

**Files:**
- Private output: `C:/tmp/pn_natuna_rebuild_current_staging_mariadb.sql.gz`
- Modify: `HANDOFF.md`
- Modify: `CPANEL-STAGING-CUTOVER-RUNBOOK.md`

- [ ] Export current local DB with all content applied.
- [ ] Replace MySQL-only collations and reject incompatible conditionals.
- [ ] Validate template default, migration registry, imported counts, routes, and checksum.
- [ ] Document that cPanel `current.sql.gz` must be replaced before `--full-staging`.
- [ ] Commit source artifacts and push; never commit private dump.