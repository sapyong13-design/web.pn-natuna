# Portal Transparansi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah `/transparansi` menjadi portal 13 layanan transparansi yang terkelompok, informatif, responsif, dan aksesibel.

**Architecture:** Artikel Joomla id 8 menjadi markup semantik portal. SQL migrasi menyimpan perubahan konten agar dapat diterapkan ulang; CSS terisolasi dengan prefiks `trans-` membentuk hero, navigasi, kelompok kartu, dan CTA tanpa JavaScript baru.

**Tech Stack:** Joomla 6, MariaDB/MySQL SQL migration, HTML, CSS.

## Global Constraints

- Pertahankan seluruh 13 URL yang ada.
- Gunakan identitas marun–emas, Fraunces, dan Plus Jakarta Sans.
- Dukung desktop 1440 px, mobile 390 px, dark mode, keyboard, dan reduced motion.
- Jangan ubah struktur menu Joomla.

---

### Task 1: Portal content and styles

**Files:**
- Create: `database/_transparansi_redesign.sql`
- Modify: `templates/pn_natuna_2026/css/template.css`

**Interfaces:**
- Consumes: artikel Joomla `pnn_content.id = 8` dan 13 URL aktif.
- Produces: elemen `.trans-page`, `.trans-hero`, `.trans-nav`, `.trans-section`, `.trans-card`, `.trans-cta`.

- [ ] **Step 1: Capture failing behavioral baseline**

Run browser checks asserting `.trans-hero`, four `.trans-section`, 13 `.trans-card`, and `.trans-cta` exist.
Expected: FAIL because current page is a flat list.

- [ ] **Step 2: Add SQL content migration**

Write one `UPDATE pnn_content` statement for id 8 containing semantic hero, anchor navigation, four sections, exactly 13 linked cards using existing URLs, and PPID/contact CTA. SVG icons must use `aria-hidden="true"`.

- [ ] **Step 3: Add isolated responsive styles**

Append one `TRANSPARANSI PORTAL 2026-07` block. Define light/dark surfaces, responsive 3/2/1-column grids, focus-visible states, restrained hover motion, and reduced-motion override.

- [ ] **Step 4: Apply migration and verify behavior**

Run: `mysql -h 127.0.0.1 -u root pn_natuna_rebuild -e "source database/_transparansi_redesign.sql"`
Expected: success with no SQL error.

Run browser checks at 1440 px and 390 px in light/dark mode.
Expected: one hero, four sections, 13 cards, one CTA, no overflow, all original URLs preserved.

- [ ] **Step 5: Commit**

```bash
git add database/_transparansi_redesign.sql templates/pn_natuna_2026/css/template.css
git commit -m "feat: redesign transparansi portal"
```
