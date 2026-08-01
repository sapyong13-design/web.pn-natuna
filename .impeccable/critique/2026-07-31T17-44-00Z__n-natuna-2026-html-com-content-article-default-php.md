---
target: halaman artikel berita (editorial article surface)
total_score: 14
max_score: 32
na_heuristics: 5,10
p0_count: 3
p1_count: 2
timestamp: 2026-07-31T17-44-00Z
slug: n-natuna-2026-html-com-content-article-default-php
---
Method: dual-agent (A: AssessmentDesignReview · B: AssessmentDetectorEvidence)

Target: permukaan artikel berita — `templates/pn_natuna_2026/html/com_content/article/default.php` + blok `.editorial-article*` di `css/template.css`. Mode: **Read**. Enam artikel diperiksa pada 1920/1366/390 (A juga 320).

## Design Health Score

| # | Heuristik | Skor | Isu kunci |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Nol aturan `:hover` untuk kartu terkait dan tombol berbagi; tanpa indikator posisi baca pada artikel 10,5 layar |
| 2 | Match System / Real World | 3 | Register kedinasan konsisten; "Berita terkait" hanya `ORDER BY publish_up DESC` sehingga tiga artikel lama menampilkan trio kartu identik |
| 3 | User Control and Freedom | 2 | Tautan kembali membuang `?start=`; daftar 14 halaman selalu mendarat di halaman 1; tanpa breadcrumb dan tanpa navigasi antar-artikel |
| 4 | Consistency and Standards | 1 | Hierarki terbalik: "Berita terkait" 40px > judul artikel 36,8px = subjudul isi 36,8px; judul sama tampil 16px rata kiri di daftar, 36,8px rata tengah di artikel |
| 5 | Error Prevention | n/a | Permukaan baca murni; tanpa input atau aksi destruktif |
| 6 | Recognition Rather Than Recall | 2 | Nol `<figcaption>` pada 14 figur; tanpa daftar isi pada artikel 5 subjudul; header lengket tidak membawa judul |
| 7 | Flexibility and Efficiency | 1 | Artikel 143 kata dan 765 kata mendapat tata letak identik; 1920px sama persis dengan 1366px, 400px margin mati per sisi |
| 8 | Aesthetic and Minimalist | 2 | "Berita PN Natuna" muncul 3× dalam 130px teratas; tanggal ganda dan bertentangan pada artikel CPNS |
| 9 | Error Recovery | 1 | "Tautan tidak dapat disalin." memakai elemen, warna, ukuran, dan posisi identik dengan pesan sukses; `aria-live="polite"`, bukan `role="alert"` |
| 10 | Help and Documentation | n/a | Bukan permukaan tugas |
| **Total** | | **14/32** | **Perlu perbaikan serius** |

## Temuan akar

`<article class="editorial-article">` bersarang di dalam `.content-primary`. Dua aturan generik (spesifisitas 0-1-1) mengalahkan seluruh desain editorial (0-1-0):

- `.content-primary h1` (template.css:3531) → judul dirancang `clamp(2.35rem, 6vw, 5.25rem)` = **81,96px**, tayang **36,8px**; `letter-spacing` −.045em → 0; warna `--editorial-ink` → maroon; plus `border-bottom` abu-abu biru #D9E0E7 di luar palet. Di 390px: dirancang 42,9px, tayang 26,4px. Yang lolos hanya `line-height: .99` — leading untuk huruf 82px, dipakai pada 36,8px.
- `.content-primary p` (template.css:3542) → `text-align: justify; hyphens: auto; max-width: 78ch` bocor ke setiap paragraf isi dan ke kicker.

Judul rata tengah adalah gejala yang terlihat; penyebabnya judul yang tidak pernah tayang sebagaimana dirancang.

## Design Specificity Verdict

**Sebagian.** Arah desainnya milik pengadilan ini — Fraunces + Plus Jakarta Sans, palet hangat bertoken, kicker emas, footer "Diterbitkan oleh Pengadilan Negeri Natuna", bulan Indonesia, schema.org NewsArticle. Tanpa logo pun blok ini terbaca sebagai terbitan resmi.

Tetapi eksekusinya generik dan lapisan paling khas justru kalah di layar: judul serif rata tengah, hero 16:9, satu kolom, tiga kartu terkait seragam. Tidak ada elemen yang hanya masuk akal untuk pengadilan — tanpa pembeda tanggal peristiwa vs tanggal terbit, tanpa tautan ke layanan yang disebut di dalam berita, nol tautan dalam badan artikel pada 6/6 artikel. Cangkang `.content-primary` berlatar #FFFFFF murni sementara token blok ini `--editorial-paper: #FFFDF9` (beda 1,02:1, tak terlihat); di mode gelap kartu halaman #19232B (biru dingin) bertemu kartu terkait #281B18 dan figur #352522 (cokelat hangat).

**Deterministic scan:** `detect.mjs` atas renderer artikel → exit 0, **nol temuan**. CLS 0 pada 18 kombinasi URL×lebar, overflow horizontal 0, semua `img` memiliki width/height, kontras teks 12,07:1 (terang) dan 11,8:1 (gelap). Detektor tidak menangkap satu pun isu di atas; semuanya berasal dari pengukuran browser dan pembacaan cascade.

**Visual overlays:** tidak ada. Assessment B mengukur lewat evaluate, tanpa injeksi skrip overlay dan tanpa live-server, jadi tidak ada tampilan bertanda di browser.

## Yang sudah bekerja

1. **Suara tipografi yang benar-benar dipilih.** Fraunces variable + Plus Jakarta Sans, isi 17,6px/31,3px (1,78), lead 20,4px/34,7px, kontras isi 17,1:1. Begitu kolomnya disempitkan, tipografi ini siap pakai apa adanya.
2. **Sebagian kontrak aksesibilitas nyata.** `prefers-reduced-motion` diuji langsung dan benar bekerja; tiga kontrol berbagi tepat 44px; `.editorial-article__share-status { min-height: 1.4em }` membuat pengumuman status menimbulkan nol pergeseran.
3. **Templat jujur digerakkan data.** Waktu baca dihitung, berita terkait lewat satu kueri yang menghormati access/language/publish window, "Diperbarui" hanya muncul bila selisih ≥24 jam, nol kalimat konten dipaku di templat.

## Priority Issues

**[P0] Judul: rata tengah, dan desainnya memang tidak pernah tayang.**
Judul artikel Pos memakan 6 baris di 1366px, 7 baris di 390px, 9 baris di 320px — semuanya rata tengah dalam sumur 500,8px (`max-width: 20ch`) di tengah kartu 1120px, rata-rata 13–20 karakter per baris. Piramida ragged tanpa tepi kiri yang stabil.
Fix: naikkan spesifisitas ke 0-2-0 (`.editorial-article .editorial-article__title`), `text-align: start`, `font-size: clamp(1.9rem, 3.4vw, 2.9rem)`, `line-height: 1.08`, `letter-spacing: -.02em`, `border-bottom: 0`, `max-width: 22ch`; header `text-align: start`. Perintah: `/impeccable typeset`.

**[P0] Seluruh teks isi rata kanan-kiri di semua lebar.**
`text-align: justify` + `hyphens: auto` tanpa kamus pemenggalan Indonesia. Celah antarkata terukur 3,1px → 6,0px → 8,3px (p90) dalam satu paragraf. Desktop: sungai putih pada baris 100–111 karakter. Mobile 390px: kolom 284px, 29–41 karakter per baris, teks retak.
Fix: `text-align: start; hyphens: manual; text-wrap: pretty; max-width: none` pada paragraf editorial, plus kolom teks `min(100%, 640px)` (≈60–74 karakter) dan jeda paragraf `1.75em` (30,8px) agar melampaui leading 31,3px. Perintah: `/impeccable typeset`.

**[P0] Kartu "Berita terkait" runtuh pada ≤390px.**
Media query ≤760px memakai `grid-template-rows: auto 1fr` + gambar `height: 100%; aspect-ratio: auto` → tinggi gambar dikendalikan tinggi judul yang membungkus. Di 320px: gambar 112×494 (rasio 0,23), kolom judul 68px, judul pecah 17 baris berisi 4–7 huruf karena `overflow-wrap: anywhere`, blok total 1.351px.
Fix: kunci `aspect-ratio: 4/3` dan `height: auto`, satu kolom penuh di ≤360px, `overflow-wrap: break-word`, tambahkan state `:hover`/`:focus-within`. Perintah: `/impeccable adapt`.

**[P1] Rezim foto: krop, nol caption, alt duplikat, nol `srcset`.**
Hero `aspect-ratio: 16/9` membuang 15,7% tinggi foto 3:2 dan 25,0% foto 4:3, sementara figur sebaris di halaman yang sama tidak dikrop sama sekali. Nol `<figcaption>` pada 14 figur meski CSS-nya sudah ditulis. Alt identik antarfigur dalam satu artikel; alt hero menyalin `<h1>`. Nol `srcset`: foto 1600px untuk slot 316px = oversample 5,06×; payload 390px 1.435–2.444 KB, sama persis dengan desktop.
Fix: hero `aspect-ratio: 3/2` pada lebar 900px, isi `image_fulltext_caption`, alt berbeda per figur, `srcset`/`sizes` 400/800/1200. Perintah: `/impeccable optimize`.

**[P1] Cincin fokus 2,32:1 gagal WCAG 2.2 SC 1.4.11 pada tema terang.**
`outline: 3px solid #d1a42f` dengan `outline-offset: 3px` berada di atas #FFFFFF → 2,32:1 (diverifikasi ulang oleh parent), di bawah ambang 3:1. Tema gelap lulus 6,88:1. Terkait: tombol kembali-ke-atas 40×40px melanggar kontrak 44px proyek dan hanya berjarak 8px dari tombol WhatsApp; hero tidak punya override mode gelap sehingga latarnya tetap #EDE6DC.
Fix: `outline-color: #7A4B00` (7,41:1) + `box-shadow: 0 0 0 5px rgba(209,164,47,.35)`, emas dipertahankan untuk tema gelap. Perintah: `/impeccable harden`.

## Persona Red Flags

**Warga awam dari ponsel, koneksi lambat:** payload 1.435–2.444 KB identik dengan desktop (29–49 detik pada 50 kbps); oversample gambar 5,06×; teks justify pada kolom 284px; judul rata tengah 7–9 baris; kartu terkait hancur di 320px; tiga widget mengambang beririsan dengan kolom baca; nol caption pada 14 figur; hero mode gelap berlatar krem terang.

**Asesor yang memindai bukti:** tanpa daftar isi/anchor pada artikel AMPUH (10,5 layar di ponsel); tanpa breadcrumb; kanal punya dua URL yang sama-sama 200; tautan kembali membuang `?start=`; tanggal bertentangan pada artikel CPNS (meta 4 Juni vs isi 03/06); "Berita terkait" tidak terkait; nol tautan dalam badan artikel padahal `/ampuh` ada di navigasi; alt tidak dapat diaudit; cincin fokus gagal; `<section>` terkait berada di dalam `<article itemtype="NewsArticle">`.

## Minor Observations

- Kolom footer 819,8px vs kolom isi 901,8px — keduanya `min(100%, 70ch)` tetapi `ch` di-resolve pada font berbeda; garis footer masuk 41px per sisi.
- Kicker terkena `max-width: 78ch` lalu rata kiri di dalam header rata tengah — tiga perataan dalam satu header.
- Jarak antarparagraf 17,6px < tinggi baris 31,3px; 15 paragraf mengalir sebagai satu blok.
- Mode gelap: heading, tautan, dan judul kartu semuanya emas (#F3C96B vs #EFC96B, beda 4/255).
- `str_word_count()` ASCII memberi "4 menit baca" untuk 765 kata = 191 kpm; 160–180 kpm lebih jujur.
- Meta membungkus di 320px dengan titik tengah menggantung di awal baris kedua.
- Elemen `<time>` diletakkan di atas judul dalam kartu terkait.
- CSS `.editorial-article__body blockquote/table/a` adalah kode mati untuk korpus ini.
- **False positive Assessment A:** "CSS 406 KB tanpa kompresi" adalah artefak server dev PHP. `.htaccess` baris 37–41 memasang `mod_deflate` untuk `text/css`; di produksi Apache aset ini terkompresi.
- **Diperbaiki saat critique berjalan:** dua lead terbaru memuat `?` (U+003F) pada posisi em dash — regresi encoding dari pipeline perbaikan ejaan sebelumnya. Sudah dipulihkan ke U+2014 dan dikunci kontrak.

## Questions to Consider

- Daftar `/berita` menampilkan judul 16px rata kiri; artikelnya 36,8px rata tengah. Mana yang berbohong tentang jenis dokumen ini?
- Blok CSS ini diberi komentar "isolated from category listings and core fallback", tetapi `.content-primary h1` diam-diam memenangkan font-size, warna, dan letter-spacing. Berapa banyak keputusan desain lain di templat ini yang juga tidak pernah sampai ke layar?
- Enam artikel, empat belas foto, nol caption — padahal CSS caption sudah menunggu. Apakah alur kerja redaksinya memang tidak punya kolom untuk menjelaskan foto?
- Pembaca desktop artikel CPNS menggulir 3,1 layar foto sebelum kalimat pertama, untuk berita 143 kata. Halaman itu memberi kabar atau memajang album?
- "Bobot halaman adalah fitur" untuk pengguna kepulauan. Mengapa ponsel 390px menerima byte yang sama persis dengan monitor 1920px?
