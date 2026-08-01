---
target: hero card slide sambutan
total_score: 24
max_score: 36
na_heuristics: 10
p0_count: 3
p1_count: 3
timestamp: 2026-07-26T14-21-32Z
slug: templates-pn-natuna-2026-hero-slider-php
---
Method: dual-agent (A: HeroDesignReview/designer · B: HeroDetectorEvidence/task). Tiga klaim kontradiktif diverifikasi ulang oleh parent.

## Design Health Score

| # | Heuristik | Skor | Isu kunci |
|---|-----------|------|-----------|
| 1 | Visibility of System Status | 2 | Status PTSP server-rendered dan kesegaran SIPP dinyatakan jujur. Tapi tombol jeda berbohong di `prefers-reduced-motion` (dikonfirmasi dua asesmen independen), tidak ada penanda posisi "1 dari 3", dan progres dot di bawah lipatan pada 1366×768. |
| 2 | Match System / Real World | 3 | Bahasa kedinasan benar, nol nada pemasaran. "Cek SIPP melalui tombol di bawah" merujuk tombol berlabel "Telusuri Perkara"; "Perlu diperbarui" ditulis dari sisi meja pengadilan. |
| 3 | User Control and Freedom | 3 | Kendali lengkap: jeda, dot, panah, keyboard, swipe, reduced-motion. Tapi kendali jeda y=812 (1366×768) dan y=638 (320×568) — di bawah lipatan. WCAG 2.2.2 lolos huruf, gagal praktik. |
| 4 | Consistency and Standards | 2 | Radius 0px melawan `--radius-card:12px`; padding 22/28/24 dan 18/16/20 di luar skala `--space-*`; headline ganti berat/tracking di batas 900px; dua CTA 14,08px vs 16px; tiga rumpun kendali di tiga posisi. |
| 5 | Error Prevention | 3 | Menolak menayangkan jumlah perkara basi. Celah: "Telusuri Perkara" membuka domain lain tanpa peringatan; dua atribut `hidden` adalah kode mati. |
| 6 | Recognition Rather Than Recall | 3 | Semua kendali berlabel. Dot tanpa teks posisi; "SIPP" harus dipetakan sendiri ke tombol berkata lain. |
| 7 | Flexibility and Efficiency | 3 | Berlaku — advokat/pihak berperkara adalah pengguna berulang. Panah keyboard, swipe, deep link ada. Jeda tidak bertahan antar-muat. |
| 8 | Aesthetic and Minimalist Design | 2 | Nama lembaga 6× di viewport pertama @1920. Tinta mati 162px di kanan anak terlebar. 97,6px anggaran vertikal milik rasio slide lain. Lima tingkat tipografi dalam pita 5,83px. |
| 9 | Error Recovery | 3 | Satu state kegagalan nyata (cache SIPP basi) ditangani: masalah dinamai, jalan keluar diberi. Kalimatnya dari sudut pandang pengadilan. |
| 10 | Help and Documentation | n/a | Pita hero bukan permukaan bantuan; bantuan hidup di Layanan Publik dan Prosedur Perkara. Kekurangan glosarium sudah dihitung di heuristik 2 dan 6. |
| **Total** | | **24/36** | **Acceptable (66,7%)** |

## Design Specificity Verdict

**Isinya spesifik, komposisinya generik.**

Yang membuat hero terasa PN Natuna semuanya warisan, bukan keputusan tata letak: foto gedung dengan Sang Merah Putih, lambang Kartika Cakra Anugraha, maroon-emas, Fraunces, kosakata PTSP/SIPP. Cabut foto dan enam kata — susunan CSS-nya bisa dipakai rumah sakit daerah, universitas swasta, atau resort tanpa satu baris diubah.

Satu-satunya upaya tanda tangan visual adalah `clip-path: polygon(0 0, 94% 0, 100% 50%, 94% 100%, 0 100%)` (template.css:11558). Pada bidang 820×443,6px, ujungnya hanya 49,2px dalam — miring 12,5° dari vertikal. Terlalu dangkal untuk keputusan, terlalu ganjil untuk kebetulan.

Tradisi peradilan yang tersedia dan tidak dipakai: prasasti, segel, nameplate lembaran negara, kop kedinasan, register perkara. Satu hal yang hanya dimiliki pengadilan — nama sebagai inskripsi — justru sedang berbagi ukuran dengan kata "Selamat".

**Deterministic scan:** `detect.mjs --json templates/pn_natuna_2026/hero-slider.php` → exit 0, 0 temuan. Detector bersih dan tidak menangkap satu pun dari sembilan isu di bawah — semuanya bersifat komposisi, hierarki, dan perilaku runtime.

**Visual overlays:** tidak tersedia. Injeksi gagal (`window.impeccableDetect` undefined). Tidak ada overlay yang terlihat pengguna; jangan diklaim ada.

**Konflik antar-asesmen, diverifikasi parent:**

| Klaim | Asesmen A | Asesmen B | Verifikasi parent |
|---|---|---|---|
| Zoom teks 200% @1366 | overflowX 32px | overflow 0 | **32px** — A benar, B false negative |
| 768×1024 copy di bawah lipatan | copyTop 1030 | tidak diuji | **copyTop 1030, fold 1024** — A benar |
| CTA tertutup bar @320×568 | 63,1% | tidak diuji | **34px dari 54px = 63%** — A benar |

Metode B untuk kontras adalah komposit CSS, bukan sampling raster (runtime tanpa sharp/canvas/pngjs). Karena itu B melaporkan cincin fokus "terlihat" sementara A — yang menyampel piksel foto sumber — menemukan rasio 1,13–1,73:1. Untuk elemen di atas foto, angka A yang berlaku.

## Overall Impression

Hero ini punya bahan yang benar dan keputusan yang belum diambil. Kontras dikunci di permukaan opak, bukan diserahkan ke crop foto. Data basi ditolak tayang. Setiap kontrol tepat 44×44px. Itu fondasi yang jarang dimiliki situs lembaga.

Yang hilang adalah penahanan diri. Wibawa kelembagaan datang dari menyebut nama sekali pada skala inskripsi, lalu membelanjakan sisa piksel untuk dua fakta yang benar-benar mengubah tindakan pembaca. Sekarang yang terjadi sebaliknya: suara terbesar diberikan pada basa-basi, suara terkecil pada ribbon operasional — dan ribbon itulah yang jatuh melewati lipatan.

Peluang tunggal terbesar: **berhenti membiarkan slide poster mendikte tinggi slide sambutan.** Itu satu perbaikan yang membuka 97,6px dan memindahkan ribbon, caption, serta seluruh kendali kembali ke dalam lipatan.

## What's Working

1. **Data jujur diwujudkan di antarmuka, bukan cuma di cron.** Ribbon menolak mencetak jumlah perkara basi dan menulis "Perlu diperbarui" + rute keluar (hero-slider.php:283-292). Prinsip Produk 3 yang benar-benar terlihat pengguna.
2. **Kontras tidak diserahkan kepada foto.** Bidang tinta opak `#29130f`: headline 17,58:1, pengantar 16,05:1, ribbon 11,67–16,19:1 — konstan lintas crop, breakpoint, dan tema. Mode gelap terverifikasi tidak mengubah satu rasio pun.
3. **Kontrak sentuh dipegang penuh dan mesin state jeda benar.** Enam kendali hero terukur tepat 44×44px, CTA 46–48px, cincin fokus 3px pada delapan kontrol. Pemisahan `userPaused` dari `timer` (template.js:1183-1197) adalah rekayasa halus yang mayoritas carousel salah.

## Priority Issues

### [P0] Tinggi hero didikte slide yang tidak sedang dilihat

**Mengapa penting:** Pada 768×1024, viewport pertama hanya langit, bendera, dan atap. Nol kata, nol CTA, nol status. Pengguna utama PRODUCT.md — warga awam dari perangkat murah dengan koneksi tidak stabil — membayar unduhan hero penuh untuk melihat foto langit.

**Bukti terverifikasi:** `.hero-slides` adalah grid dengan semua slide di `grid-area:1/1`, jadi tinggi baris = slide tertinggi. Poster Zona Integritas 960px × (941/1672) = 540,3px. Terukur `.hero-slides` 541,2px sementara copy sambutan 443,6px → **97,6px ruang mati milik rasio slide lain**. Di 768: `slidesHeight 1089`, `copyTop 1030`, `fold 1024`. Di 1366×768: ribbon bottom 770, chip y=807, kendali y=812–856 — semua di bawah lipatan. `transform: translateY(-32px)` hanya menambal 32 dari 97,6px.

**Fix:** Batasi lebar poster, cabut tambalan.
```css
@media (min-width: 901px) {
  .home-slider .hero-slide-integrity { width: min(820px, 100%); }
  .hero-cinema .hero-slides { min-height: 462px; }
}
@media (max-width: 900px) { .hero-cinema .hero-slides { min-height: 420px; } }
```
Hapus blok `transform: translateY(-32px)` (template.css:14467-14473). Turunkan `.hero.home-slider` padding-bottom ke `var(--space-6)` di ≥1024.

**Command:** `/impeccable layout`

### [P0] CTA primer tertutup 63% oleh bottom bar di 320×568

**Mengapa penting:** Satu-satunya jalan ke direktori layanan dua pertiga tersembunyi pada paint pertama, di baseline QA yang HANDOFF sendiri sebutkan.

**Bukti terverifikasi:** CTA `top 478, bottom 532, height 54`. `NAV.mobile-quick-actions` `position:fixed, top 498, height 70`. Tertutup **34px dari 54px = 63%**. Di layar sama, FAB aksesibilitas menimpa kartu tinta; kendali slider y=638, 70px di bawah lipatan dan di belakang bar. 390×844 aman — masalahnya spesifik pada tinggi ≤620px.

**Fix:**
```css
@media (max-width: 760px) { .hero.home-slider { padding-bottom: calc(var(--space-8) + 72px); } }
@media (max-height: 620px) {
  .hero.home-slider { padding-bottom: calc(var(--space-6) + 72px); }
  .hero-cinema .hero-welcome-copy { padding-block: var(--space-4); }
}
```

**Command:** `/impeccable adapt`

### [P0] Reflow WCAG 1.4.10 gagal pada zoom teks 200%

**Mengapa penting:** WCAG 2.2 AA adalah kontrak yang diuji mesin di proyek ini. Ini kegagalan SC, bukan preferensi.

**Bukti terverifikasi:** `overflowX 32px` @1366 dengan root font-size 32px. `clamp(3.25rem, 4.5vw, 4rem)` terbalik — batas bawah berbasis rem (104px) mengalahkan batas atas 64px. Headline menjadi 5 baris/520px; `.hero-slides` membengkak ke 1011px; ribbon, kedua CTA, caption, dan seluruh kendali jatuh di bawah lipatan.

**Fix:** Ganti batas bawah rem menjadi berbasis viewport atau tambahkan pagar: `font-size: clamp(2.6rem, 4.5vw, 4rem)` dengan `max-font-size` efektif via `min()`, dan `overflow-wrap: break-word` pada `.hero-welcome-copy h2`.

**Command:** `/impeccable harden`

### [P1] Hierarki mati di bawah headline; CTA sekunder lebih besar dari primer

**Mengapa penting:** Cacat yang tim sudah kenali dan perbaiki di kartu berita (HANDOFF polish 25 Jul butir 1) selamat di slide sambutan — dan di sini yang kehilangan bobot adalah tombol tindakan.

**Bukti:** Tangga @1366: 61,47 / 16,392 / 16 / 14,08 / 12,16 / 10,56px. Satu tebing 3,75× lalu lima tingkat dalam pita 5,83px (rasio 1,16/1,14/1,16/1,15 — praktis identik). `.is-primary` mendapat `var(--step--1)` = 14,08px; saudaranya tidak punya kelas itu dan mewarisi 16px → **sekunder 13,6% lebih besar dari primer**. Penyebabnya aturan mati `a.is-primary + a.is-primary` (template.css:11751-11756) yang tidak pernah cocok.

**Fix:** Alamatkan kedua tautan, bukan hanya `.is-primary`. Naikkan pengantar ke `var(--step-1)`, ganti `max-width:630px` → `52ch`, naikkan `.hero-service-ribbon p strong` ke `var(--step--1)`, hapus aturan mati. Tangga menjadi 61,47/19,2/16/14,08/10,56 — empat langkah jelas.

**Command:** `/impeccable typeset`

### [P1] Sapaan setara nama lembaga melemahkan otoritas

**Mengapa penting:** Dalam identitas kelembagaan — masthead, segel, prasasti, papan nama pengadilan — sapaan tidak pernah sederajat dengan nama. Otoritas tipografi kedinasan datang dari penahanan diri, bukan volume. Pengadilan tidak meninggikan suara untuk dipercaya.

**Bukti:** Baris sapaan menempati 61,5px dari blok headline 184,4px = **33,4% massa elemen terbesar di halaman**, untuk kata yang tidak memutuskan apa pun. Nama lembaga terdorong ke baris 2-3; fiksasi pertama pola-F jatuh pada "Selamat Datang di". Nama lembaga dinyatakan 6× di viewport pertama @1920. Navigasi per-heading memperdengarkan nama dua kali beruntun (h1 lalu h2). Pada zoom teks 200%, sapaan menghabiskan dua dari lima baris dan nama lembaga terdorong keluar layar.

**Fix:** Pertahankan kehangatannya, cabut pangkatnya — eyebrow emas kapital `var(--step--1)`/800/`.14em`, `color: var(--hero-gold)` (#e2b94f di atas #29130f = 9,45:1). Hapus `<br>`. Blok headline turun 184,4 → ~152px; 32px kembali, tepat sebesar kelebihan ribbon dari lipatan.

**Command:** `/impeccable typeset`

### [P1] Cincin fokus gagal 1.4.11 di atas foto; tombol jeda berbohong

**Mengapa penting:** Tiga temuan yang tidak akan tertangkap penguji kontras otomatis karena melibatkan foto, bukan latar terdeklarasi — dan satu di antaranya kendali yang aktif menjanjikan sesuatu yang tidak pernah terjadi.

**Bukti:** Outline `3px solid #b98f24` dengan `outline-offset:3px` digambar di luar tombol, langsung di atas foto. Piksel sumber di belakang dot pertama rgb(218,179,153), di belakang panah "berikutnya" rgb(194,150,126) → rasio **1,13–1,73:1** di seluruh rentang alpha scrim, tidak pernah mencapai lantai 3:1. Cincin sama 6,54:1 di belakang tombol jeda semata karena foto di situ kebetulan gelap; crop berubah di empat breakpoint.

Tombol jeda: `pauseButton.hidden = reducedMotion` (template.js:1238) dikalahkan `.home-slider .hero-pause{display:grid}` (template.css:14017). **Dikonfirmasi dua asesmen independen** di bawah emulasi reduced-motion: `display:"grid"`, `checkVisibility()===true`, label berganti ke "Lanjutkan pergantian slide otomatis", sementara `start()` return dini. HANDOFF butir 6 mendokumentasikan perilaku sebaliknya. Kelas bug sama pada `[data-service-status] hidden` (hero-slider.php:279) yang dikalahkan `display:grid`.

**Fix:** Pelat tinta di belakang cincin fokus (`box-shadow: 0 0 0 6px #29130f` → terkunci 9,45:1 apa pun crop). Satukan panah ke `.hero-slider-controls`, `bottom:34px` → `18px`, tambahkan penghitung "1 dari 3" (pola sudah ada di `.mobile-rail-status`). `.home-slider .hero-pause[hidden]{display:none}` atau beri `disabled` + label jujur. Ganti `hidden` pada status dengan `visibility:hidden` agar CLS 0,020 tetap dipegang.

**Command:** `/impeccable harden`

## Persona Red Flags

**Jordan (awam pertama kali):** 45,6% viewport pertama @1366×768 habis sebelum pesan sambutan dimulai. Kata berukuran penuh pertama yang ia baca adalah keramahan, bukan "apa yang bisa saya urus di sini". "Status PTSP" tanpa penjelasan pendamping meski PRODUCT.md mengizinkan. "Cek SIPP melalui tombol di bawah" — tombolnya berkata "Telusuri Perkara"; ia harus menebak pemetaannya. Tautan itu membuka domain lain tanpa penanda eksternal (sengaja dicabut, template.css:3591). Di 768×1024 ia melihat foto bendera dan tidak ada apa-apa lagi.

**Sam (pengguna aksesibilitas):** Cincin fokus 1,13–1,73:1 di atas foto — gagal SC 1.4.11. Urutan fokus melompat x83 → x275 → x1215 → x1267 → x589 → x733. `aria-label` pada `<div>` tanpa role; mayoritas AT membuangnya, jadi ribbon tidak punya nama grup. Tombol jeda tetap fokusabel di reduced-motion dan tidak melakukan apa pun. Zoom teks 200% → reflow gagal, overflowX 32px. Zoom halaman 200% → FAB aksesibilitas menimpa headline 24×44px.

**Casey (mobile terdistraksi):** 320×568 — bar tetap menutupi 63% CTA primer pada paint pertama; FAB menimpa kartu tinta; kendali slider 70px di bawah lipatan dan di belakang bar. Label CTA turun ke 12,16px di 390/320 — sama persis dengan `strong` ribbon, jadi tombol tindakan terbaca sebagai label informasi. Teks paling terbaca di paruh bawah hero mobile adalah papan nama di dalam foto, mengulang header. **Kabar baik:** 390×844 benar-benar bekerja — semua di atas lipatan, target 44–48px, `fetchpriority=high` + srcset 480/768/1200/1536.

## Minor Observations

- `text-wrap: balance` (template.css:11598) deklarasi mati: tiga baris sudah dipaksa `display:block` + `<br>`.
- `.hero-photo-chip` mengulang papan nama di dalam fotonya sendiri, `ignored:true` di pohon aksesibilitas, `display:none` di ≤900px, dan y=807 di 1366×768. Empat alasan menghapus, nol kontra.
- Bidang tinta 820px punya tinta mati 162px di kanan anak terlebar, 273,3px di kanan headline, 421,5px di kanan baris CTA. Di 1024 memakan 80% lebar layar; gedung tinggal 160px.
- Padding 22/28/24px dan 18/16/20px di luar skala `--space-*`; asimetri 22 atas vs 24 bawah tanpa alasan.
- Headline ganti kepribadian di batas 900px: 760/-0,045em/lh 0,92 → 700/-0,035em/lh 1,0.
- `box-shadow: rgba(185,143,36,.4) 0 10px 26px` pada CTA primer — satu-satunya elemen bercahaya di beranda (`--shadow-card` hanya `rgba(29,39,48,.06)`). Terbaca sebagai tombol produk, bukan kedinasan.
- Mode gelap tidak mengubah satu piksel pun di hero. Benar secara kontras, tapi hero jadi tidak ikut bertema: di mode terang header krem bertemu hero nyaris hitam pada garis keras.
- CLS beranda 0,0077 @1366 — jauh di bawah ambang. Sumber terbesar `mod-custom custom`, bukan hero.
- `focusin` → `stop()` tanpa `focusout` → `start()` (template.js:1287); `syncControls()` tidak ikut dipanggil sehingga `aria-live` tetap "off".

## Questions to Consider

- Kalau papan nama di Jalan Batu Sisir tidak menuliskan "Selamat Datang di" di atas nama pengadilan, kenapa berandanya menuliskannya — pada ukuran yang sama pula?
- Headline adalah hal terbesar di halaman dan tidak mengubah apa pun bagi pembaca. Ribbon terkecil dan menentukan apakah orang jadi menyalakan motor. Seperti apa hero ini kalau ukuran tipe mengikuti konsekuensi, bukan kebiasaan?
- Pantaskah slide yang sedang tidak dilihat pembaca ikut memutuskan komposisi slide yang sedang dilihatnya?
- Untuk orang yang sidangnya besok dan tinggal tiga jam feri dari Ranai, kalimat mana di hero ini yang menenangkan dia?
