# Public Facility Documentary Photos Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refresh PTSP, disability-access, and Posbakum pages plus homepage facility gallery with current cinematic documentary photography.

**Architecture:** Produce one canonical optimized PTSP WebP from user-supplied source, then update Joomla content through one new idempotent migration. Reuse global `data-maklumat-zoom` lightbox behavior and add one shared `.facility-documentary` CSS component instead of page-specific variants.

**Tech Stack:** Joomla content in MySQL, idempotent SQL migrations, PHP migration tooling, ImageMagick/Pillow image processing, HTML figure markup, CSS, Chromium.

## Global Constraints

- Preserve service facts, requirements, flow, hours, contacts, navigation, and PTSP infographics.
- PTSP output path: `images/layanan/gallery/ruang-ptsp-2026.webp`.
- Disability asset: `images/layanan/gallery/akses-disabilitas-2026.webp`.
- Posbakum asset: `images/layanan/gallery/posbakum-2026.webp`.
- Photography edits are limited to crop, exposure, white balance, contrast, highlights, shadows, and light sharpening; no generative alteration.
- Joomla changes use a new idempotent migration; never edit `20260713_facility_gallery_photos.sql`.
- No carousel, autoplay, parallax, new modal, or database schema change.

---

### Task 1: Cinematic PTSP asset

**Files:**
- Create: `images/layanan/gallery/ruang-ptsp-2026.webp`
- Test: `tools/test_public_facility_photos.php`

**Interfaces:**
- Consumes: user-supplied 1568×1045 PTSP photograph from current conversation attachment.
- Produces: optimized WebP preserving court emblem, PTSP wording, five staff, desks, and room context.

- [ ] Write focused asset test asserting file existence, WebP MIME, width at least 1400 px, landscape ratio between 1.65 and 1.9, and size below 500 KB.
- [ ] Run `C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_public_facility_photos.php`; expect failure for missing PTSP asset.
- [ ] Locate exact uploaded 1568×1045 source by dimensions/checksum; do not substitute `photo-candidates.jpg` or an older repo image without matching the attachment.
- [ ] Crop to cinematic landscape while preserving required subjects, apply restrained tonal correction, export quality-optimized WebP at source-safe resolution.
- [ ] Run focused test; expect asset assertions to pass.
- [ ] Visually inspect the WebP against source: no altered faces, text, uniforms, furniture, or room content.
- [ ] Commit with `git commit -m "assets: add cinematic PTSP facility photo"`.

### Task 2: Idempotent Joomla content migration

**Files:**
- Create: `database/migrations/20260716_public_facility_documentary_photos.sql`
- Modify: `tools/test_public_facility_photos.php`

**Interfaces:**
- Consumes: existing article IDs resolved by exact aliases `/jenis-layanan-ptsp`, `/layanan-disabilitas`, `/posbakum`, and module ID 480.
- Produces: one `.facility-documentary` figure on each route and updated PTSP gallery card.

- [ ] Extend test to assert migration exists, targets module `id = 480` with module/title guards, targets each article narrowly, references all three canonical assets, includes exact approved alt/captions, lazy loading, decoding async, and `data-maklumat-zoom`.
- [ ] Assert migration does not contain old PTSP briefing reference in resulting module content and does not rewrite service copy outside insertion/replacement boundaries.
- [ ] Run focused test; expect migration assertions to fail.
- [ ] Build migration from current DB article content, preserving all text and existing infographics byte-for-byte outside photo blocks.
- [ ] For PTSP insert figure after opening service facts and before `Layanan per Kepaniteraan`.
- [ ] For disability move/replace current photo block after opening service introduction with latest WebP.
- [ ] For Posbakum move/replace current photo block after hours/location and before `Apa yang Bisa Anda Peroleh?`.
- [ ] Update module 480 PTSP image and alt only; retain four cards and existing disability, Posbakum, and location cards.
- [ ] Run focused test; expect `public facility photo contract: ok`.
- [ ] Apply via `python tools/apply-db-migrations.py --mysql C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe --reapply`; expect migration recorded/applied without SQL errors.
- [ ] Re-run application command; expect idempotent unchanged result.
- [ ] Commit with `git commit -m "content: refresh public facility photography"`.

### Task 3: Shared documentary presentation

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify: `tools/test_public_facility_photos.php`

**Interfaces:**
- Consumes: `.facility-documentary`, `.facility-documentary__media`, and `.facility-documentary__caption` markup from Task 2.
- Produces: responsive cinematic frame, hover/focus, dark mode, and reduced-motion behavior.

- [ ] Extend focused test with CSS assertions for component base, `:focus-visible`, `body.is-dark`, mobile breakpoint, and reduced-motion rule.
- [ ] Run focused test; expect CSS assertions to fail.
- [ ] Add shared component using existing radius, border, shadow, surface, ink, and accent variables.
- [ ] Set desktop media ratio around 16:8; use per-route `object-position` classes only when visual inspection proves necessary.
- [ ] At `max-width:760px`, use taller ratio and keep caption readable without horizontal overflow.
- [ ] Add restrained image scale on hover/focus and disable transition under reduced motion.
- [ ] Run focused test; expect success.
- [ ] Commit with `git commit -m "style: add documentary facility panels"`.

### Task 4: Browser and regression verification

**Files:**
- Modify only if an observed defect requires correction: files from Tasks 1–3.

**Interfaces:**
- Consumes: local Joomla at `http://localhost:8080`.
- Produces: observed desktop/mobile, lightbox, dark-mode, gallery, and content-preservation evidence.

- [ ] Run `tools/test_public_facility_photos.php` and `tools/test_homepage_modules.php`; expect both `: ok`.
- [ ] At 1366×768 inspect `/jenis-layanan-ptsp`, `/layanan-disabilitas`, and `/posbakum`: correct image, approved caption, agreed ordering, complete image load, and preserved service content.
- [ ] Confirm PTSP `biaya-jenis-layanan.png` and `waktu-layanan.png` remain present.
- [ ] Open and close each documentary lightbox with mouse, Escape, and keyboard focus.
- [ ] At 390×844 verify all three routes have zero horizontal overflow and acceptable subject crop.
- [ ] Toggle dark mode and verify caption, border, focus ring, and lightbox contrast.
- [ ] Inspect homepage Galeri Fasilitas Publik: PTSP uses new WebP; four cards and other three images/routes remain unchanged.
- [ ] If a defect appears, first add a focused assertion where automatable, confirm failure, apply minimal fix, then repeat all checks.
- [ ] Request code review against `docs/superpowers/specs/2026-07-16-public-facility-documentary-photos-design.md`; resolve all Critical/Important findings.
- [ ] Run fresh final focused tests before completion.
