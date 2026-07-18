# Mobile Navigation Information Architecture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merapikan arsitektur menu dan drawer mobile tanpa memutus route publik atau navigasi desktop.

**Architecture:** Migrasi SQL idempoten mengubah label, parent, heading, dan published state menu Joomla lalu membangun ulang nested set. Template JS menambahkan link ringkasan khusus drawer dan active-scroll; CSS memakai token drawer serta memperbaiki ukuran dan status mode gelap.

**Tech Stack:** Joomla 5, MySQL 8.4, PHP 8.3, Python 3, vanilla JavaScript, CSS.

## Global Constraints

- Route artikel existing tidak berubah.
- Perubahan DB wajib migrasi baru dan replay-safe.
- Parent landing page tetap bisa dibuka melalui link Ringkasan pada drawer.
- Target sentuh minimum 44px.
- Status mode gelap hanya satu kata: Mati atau Aktif.

---

### Task 1: Kontrak

**Files:**
- Create: `tools/test_mobile_navigation_audit.php`
- Create: `tools/test_mobile_menu_migration.py`

- [ ] Tulis kontrak source drawer dan migrasi DB sementara.
- [ ] Jalankan keduanya dan pastikan gagal karena behavior belum ada.

### Task 2: Migrasi menu

**Files:**
- Create: `database/migrations/20260722_mobile_navigation_information_architecture.sql`

- [ ] Upsert heading menu, pindahkan item existing, ubah label, unpublish item internal.
- [ ] Bangun ulang nested set global secara deterministik.
- [ ] Jalankan tes migrasi sampai hijau, termasuk tiga replay identik.
- [ ] Terapkan registry migrasi lokal dengan `--reapply`.

### Task 3: Drawer mobile

**Files:**
- Modify: `templates/pn_natuna_2026/index.php`
- Modify: `templates/pn_natuna_2026/js/template.js`
- Modify: `templates/pn_natuna_2026/css/template.css`

- [ ] Ubah shortcut ke route kanonis dan status ke Mati/Aktif.
- [ ] Tambahkan link ringkasan runtime dan active-scroll.
- [ ] Tambahkan token drawer, font tingkat ketiga, indentasi, dan footer ringkas.
- [ ] Jalankan kontrak source sampai hijau.

### Task 4: Verifikasi

**Files:**
- Modify: `HANDOFF.md`

- [ ] Verifikasi semua route menu lokal dengan URL absolut.
- [ ] Verifikasi drawer 390×844 dan 320×568 pada light/dark mode.
- [ ] Jalankan seluruh kontrak fokus.
- [ ] Catat perubahan dan buat satu commit lokal; jangan push.