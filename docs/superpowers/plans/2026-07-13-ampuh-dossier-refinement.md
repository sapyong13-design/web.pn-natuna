# AMPUH Dossier Refinement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah `/ampuh` menjadi arsip premium Dossier Institusional yang lebih ringkas, berwibawa, responsif, dan mudah dipindai tanpa mengubah data atau URL Drive.

**Architecture:** Renderer menambah struktur presentasional untuk hero, toolbar, select mobile, dossier rows, dan lampiran. JavaScript menyatukan state filter desktop/mobile. CSS mengganti tampilan kartu lama dengan dossier editorial token-based. Focused contracts dan browser QA menjaga data, aksesibilitas, performa visual, dark mode, dan batas tinggi mobile.

**Tech Stack:** PHP 8.3, Joomla 6.1, vanilla JavaScript, CSS template PN Natuna, Python/PHP focused contracts, Chromium QA.

## Global Constraints

- Dataset tetap 27 GOBI, 82 checklist unik, 405 sub-checklist, 2.043 dokumen.
- URL Drive dan migration tidak berubah.
- Tidak menambah dependency/font/gambar.
- Semua disclosure, search, filter persistence, dark mode, dan keyboard behavior tetap bekerja.
- Mobile tertutup maksimal 3.900 px pada 390×844 dan tanpa overflow horizontal pada 320–430 px.
- Motion hanya opacity dan transform; reduced motion menonaktifkannya.
- Kontras teks minimum 4.5:1.

---

### Task 1: Dossier renderer semantics

**Files:**
- Modify: `templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php`
- Modify: `tools/test_ampuh_directory_renderer.php`

- [ ] Tulis failing fixture assertions untuk hero secondary/watermark, `Indeks Koleksi`, mobile GOBI select 28 opsi, struktur nomor/title/meta dossier row, folder path, dan nomor sub-checklist tunggal.
- [ ] Jalankan PHP contract dan konfirmasi RED pada hooks baru.
- [ ] Implementasikan markup minimal, tetap escape semua data dan mempertahankan ARIA disclosure.
- [ ] Jalankan PHP contract sampai GREEN.

### Task 2: Unified GOBI filtering

**Files:**
- Modify: `templates/pn_natuna_2026/js/template.js`
- Modify: `tools/test_ampuh_directory_interactions.py`

- [ ] Tambahkan failing behavior fixture untuk select mobile, sinkronisasi dengan 27 tombol desktop, filter clear, search persistence, dan no-op halaman lain.
- [ ] Jalankan interaction contract dan konfirmasi RED.
- [ ] Implementasikan satu `setSelectedGobi(value)` yang memperbarui tombol `aria-pressed`, select value, visibility, result count, dan disclosure reset.
- [ ] Jalankan interaction contract sampai GREEN.

### Task 3: Dossier visual system

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify: `tools/test_ampuh_directory_renderer.php`

- [ ] Tambahkan failing scoped CSS assertions untuk hero ≤360/300 px, toolbar ≤190/230 px, row 76–88/72–82 px, desktop rail, mobile select, indeks 2×2, no per-row shadow, wrapping, focus, dark contrast, dan allowed motion.
- [ ] Jalankan renderer contract dan konfirmasi RED.
- [ ] Ganti blok AMPUH dengan visual Dossier Institusional: hero asimetris, grain pseudo-element, index ribbon, compact toolbar, rail, dossier rows, table-of-contents checklist, attachment file list.
- [ ] Jalankan renderer contract sampai GREEN.

### Task 4: Browser QA and refinement

**Files:**
- Modify only files above when browser evidence exposes defects.

- [ ] Jalankan importer, renderer, interaction, E2E, dan navbar focused suites.
- [ ] QA desktop 1440×1000 light/dark, tablet 900×900, mobile 390×844 light/dark, dan 320×700.
- [ ] Ukur mobile closed-page height ≤3.900 px, toolbar ≤230 px, row 72–82 px, no overflow.
- [ ] Uji disclosure chain, file search, clear search, button/select sync, keyboard, reduced motion, dan console.
- [ ] Perbaiki hanya defect teramati, ulang focused tests dan browser QA.
- [ ] Commit refinement final.
