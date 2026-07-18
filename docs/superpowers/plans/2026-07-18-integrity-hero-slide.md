# Integrity Hero Slide Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambah poster integritas sebagai slide kedua hero dan memindahkan Berita & Pengumuman ke slide ketiga.

**Architecture:** Renderer hero tetap menjadi boundary markup tunggal. Poster disimpan sebagai WebP lokal, dirender sebagai tautan full-artwork tanpa overlay copy, dan memakai carousel generik yang sudah mendukung jumlah slide dinamis.

**Tech Stack:** Joomla 5, PHP 8.3, CSS, vanilla JavaScript, Pillow.

## Global Constraints

- Urutan slide wajib sambutan, poster, berita.
- Poster menaut ke `/zona-integritas`.
- Seluruh artwork wajib terlihat tanpa crop pada desktop dan mobile.
- Tidak menambah library runtime atau JavaScript khusus slide.

---

### Task 1: Kontrak dan aset

**Files:**
- Create: `tools/test_integrity_hero_slide.php`
- Create: `images/hero/integritas-tolak-gratifikasi-pungli-2026.webp`

- [ ] Tulis kontrak yang memeriksa tiga slide, urutan, route, aset, dot, dan styling contain.
- [ ] Jalankan kontrak dan pastikan gagal karena slide belum ada.
- [ ] Konversi PNG sumber menjadi WebP 1672×941 tanpa mengubah rasio.
- [ ] Verifikasi ukuran, format, dan batas file.

### Task 2: Renderer dan layout

**Files:**
- Modify: `templates/pn_natuna_2026/hero-slider.php`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Test: `tools/test_integrity_hero_slide.php`

- [ ] Tambahkan slide tautan poster sebelum `.hero-slide-news`.
- [ ] Tambahkan dot indeks 1 untuk poster dan ubah dot berita menjadi indeks 2.
- [ ] Tambahkan CSS full-artwork dengan `object-fit: contain`, focus ring, dan aturan mobile.
- [ ] Jalankan kontrak sampai hijau.

### Task 3: Verifikasi dan handoff

**Files:**
- Modify: `HANDOFF.md`

- [ ] Jalankan kontrak hero dan homepage.
- [ ] Verifikasi browser desktop dan mobile, tanpa menjalankan media.
- [ ] Catat aset, urutan slide, route, dan kontrak di handoff.
- [ ] Buat satu commit lokal dan jangan push.