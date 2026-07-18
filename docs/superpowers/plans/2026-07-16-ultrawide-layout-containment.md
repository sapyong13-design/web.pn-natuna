# Ultra-wide Layout Containment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membatasi layout PN Natuna pada viewport ultra-wide agar tetap proporsional dan terpusat.

**Architecture:** Tambahkan satu breakpoint desktop ultra-wide pada akhir stylesheet. Shell section utama mendapat `max-width: 1920px` dan `margin-inline: auto`; sticky navigation memakai inset yang mengikuti shell.

**Tech Stack:** Joomla 5 template, CSS, PHP contract test, Chromium.

## Global Constraints

- Tidak ada deteksi zoom JavaScript.
- Tidak mengubah layout pada viewport `1920px` atau lebih kecil.
- Semua perubahan menjadi satu commit lokal dan tidak di-push.

---

### Task 1: Kontrak Ultra-wide

**Files:**
- Create: `tools/test_ultrawide_layout.php`
- Modify: `templates/pn_natuna_2026/css/template.css`

**Interfaces:**
- Consumes: section shell yang sudah ada.
- Produces: breakpoint `@media (min-width: 1921px)` dengan shell maksimum `1920px`.

- [ ] **Step 1: Tulis kontrak yang memeriksa breakpoint, selector shell, batas lebar, centering, dan sticky navigation.**
- [ ] **Step 2: Jalankan `php tools/test_ultrawide_layout.php`; harapkan kegagalan karena aturan belum ada.**
- [ ] **Step 3: Tambahkan aturan ultra-wide minimal pada akhir stylesheet.**
- [ ] **Step 4: Jalankan kontrak; harapkan `ultrawide layout contract: ok`.**

### Task 2: Verifikasi Visual dan Commit

**Files:**
- Verify: `templates/pn_natuna_2026/css/template.css`

- [ ] **Step 1: Buka beranda pada 1366×768 dan pastikan shell selebar viewport.**
- [ ] **Step 2: Buka beranda pada 3840×2160 dan pastikan shell 1920px serta terpusat.**
- [ ] **Step 3: Periksa screenshot visual ultra-wide dan tampilan normal.**
- [ ] **Step 4: Commit seluruh spec, plan, test, dan CSS sebagai satu commit lokal; jangan push.**