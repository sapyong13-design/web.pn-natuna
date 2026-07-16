# BMN Announcements and Facility Panel Sizing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Import exactly two non-duplicate BMN announcements with distinct official-document thumbnails and enlarge only three facility detail panels.

**Architecture:** Download each public Drive PDF, verify signature, rasterize page one into optimized WebP, then insert/upsert category-13 Joomla articles through a new idempotent migration. Deduplication resolves an existing row by document URL, exact alias, or normalized title before insert. Facility sizing uses per-figure modifier classes and leaves homepage facility gallery selectors untouched.

**Tech Stack:** Python/PDF rasterizer, WebP, Joomla/MySQL, idempotent SQL migrations, PHP focused tests, CSS, Chromium.

## Global Constraints

- Import exactly two BMN announcements and no other source items.
- Never duplicate by document URL, exact alias, or normalized title.
- Second migration application must leave exactly two BMN rows.
- Use 16 July 2026 as verified retrieval date, not claimed document publication date.
- Use distinct first-page WebP thumbnails from official PDFs.
- Keep homepage Galeri Fasilitas markup and `.facility-thumb` CSS unchanged.
- Preserve all service content, lightbox, captions, dark mode, focus, and reduced motion.

---

### Task 1: Official BMN document thumbnails

**Files:**
- Create: `images/pengumuman/bmn-penetapan-pemenang-lelang-2026.webp`
- Create: `images/pengumuman/bmn-pengumuman-lelang-2026.webp`
- Create: `tools/test_bmn_announcements.php`

- [ ] Write failing test for both WebPs: existence, WebP MIME, minimum 900px width, below 500KB, and different SHA-256 hashes.
- [ ] Run focused test; expect missing-thumbnail failures.
- [ ] Download the two exact Drive documents, verify `%PDF-` signature and public filenames, and keep downloads under ignored `tmp/` only.
- [ ] Rasterize page one of each PDF without content alteration, normalize canvas ratio, and export optimized distinct WebPs.
- [ ] Run focused test; expect thumbnail checks to pass.
- [ ] Visually compare thumbnails with PDF page one.
- [ ] Commit `assets: add BMN announcement thumbnails`.

### Task 2: Anti-duplicate BMN announcement migration

**Files:**
- Create: `database/migrations/20260716_import_bmn_announcements.sql`
- Modify: `tools/test_bmn_announcements.php`

- [ ] Extend test to assert both exact titles, aliases, Drive URLs, thumbnail paths, category 13, state/access 1, safe external link attributes, retrieval-date explanation, and three dedupe predicates.
- [ ] Run focused test; expect migration failure.
- [ ] Write migration that computes `@bmn_id` from OR predicates: metadata document URL, exact alias, or normalized lowercase/trimmed title.
- [ ] Insert only when `@bmn_id IS NULL`; otherwise update the resolved row. Before insert, assert no second matching row is selected.
- [ ] Store canonical document URL in metadata and article body. Set `image_intro`/`image_fulltext` to each distinct WebP.
- [ ] Use timestamps on 16 July 2026 with deterministic one-second ordering matching source homepage.
- [ ] Add readable payload comments for audit.
- [ ] Run focused test; expect success.
- [ ] Apply migration, query matching rows by all three identities, and assert total distinct IDs equals two.
- [ ] Apply migration again; assert count remains two and IDs remain unchanged.
- [ ] Verify no existing Posbakum article changed.
- [ ] Commit `content: import BMN announcements`.

### Task 3: Per-page facility panel sizing

**Files:**
- Create: `database/migrations/20260716_facility_panel_size_variants.sql`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify: `tools/test_public_facility_photos.php`

- [ ] Add failing assertions for exact alias/hash-guarded class additions: `facility-documentary--disability` and `facility-documentary--posbakum`.
- [ ] Add failing CSS assertions for PTSP `380/230px`, disability `350/220px`, and Posbakum `360/220px` desktop/mobile heights.
- [ ] Assert migration and CSS contain no `.facility-thumb` or module-480 update.
- [ ] Run focused test; expect failures.
- [ ] Add idempotent alias/hash-guarded migration that only adds modifier classes to disability and Posbakum figures; PTSP modifier already exists.
- [ ] Add per-variant CSS heights. PTSP remains `contain`; disability uses `cover` with verified object-position; Posbakum uses `contain` if browser proves cover crops the service desk.
- [ ] Apply migration and rerun test; expect success.
- [ ] Commit `style: resize facility detail photography`.

### Task 4: End-to-end verification

- [ ] Run `tools/test_bmn_announcements.php`, `tools/test_public_facility_photos.php`, and `tools/test_homepage_modules.php`; expect all `: ok`.
- [ ] Query category 13 by both aliases, both normalized titles, and both Drive URLs; assert every identity maps to exactly two distinct IDs total.
- [ ] Reapply all migrations and repeat count/ID assertion.
- [ ] At `/pengumuman`, verify two BMN cards are newest, distinct, and use different thumbnails.
- [ ] On homepage, verify feature and compact BMN items use different images and correct article links.
- [ ] Open both article pages and both Drive links; verify safe new-tab attributes.
- [ ] Verify three detail panel heights at 1366×768 and 390×844, zero overflow, and acceptable framing.
- [ ] Verify homepage Galeri Fasilitas remains four cards with prior thumbnail dimensions.
- [ ] Request code review; resolve all Critical and Important findings.
- [ ] Run fresh final tests and commit verification fixes if needed.
