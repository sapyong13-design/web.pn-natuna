---
target: halaman berita /berita dan halaman artikel
total_score: 20
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 3
timestamp: 2026-08-01T16-04-36Z
slug: atuna-2026-html-com-content-category-blog-item-php
---
Method: dual-agent (A: AssessA/designer · B: AssessB/task). Assessment A's yield came back as a stub; the full report was recovered from its transcript and every load-bearing claim was re-verified independently by the parent before entering this report.

## Design Health Score

| # | Heuristik | Skor | Masalah utama |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | "Bagikan artikel" mati di peramban tanpa Web Share: `nb.hidden=true` dikalahkan `.editorial-article__share button{display:inline-flex}` (css:12371). Dibuktikan langsung: `hidden` diset, computed `display:flex`, kotak 147x44, diklik -> status `aria-live` tetap kosong. Tidak ada state `:visited` untuk arsip 84 item. |
| 2 | Match System / Real World | 2 | Status kosong memakai teks Inggris mentah Joomla: "There are no articles in this category. If subcategories display on this page, they may have articles." (`blog.php:68`, tanpa terjemahan id-ID). 93 dari 93 kapsi berbunyi "Dokumentasi Pengadilan Negeri Natuna - <tanggal>"; tidak satu pun menjelaskan fotonya, sementara deskripsi aslinya menganggur di `alt`. |
| 3 | User Control and Freedom | 3 | Kuat: `?start=` divalidasi host+path sehingga tautan kembali mendarat di halaman daftar yang persis. Lemah: 14 halaman tanpa tahun, bulan, atau pencarian. |
| 4 | Consistency and Standards | 1 | Dua sistem cincin fokus di satu situs: artikel memakai `#7a4b00`+halo justru karena tim mengukur emasnya 2,32:1 dan menolaknya (css:12384-12386); daftar tetap memakai `#d5a530` - 2,25:1, kegagalan yang sama, satu blok CSS di sebelahnya. Badan teks 20px di desktop, 16px di seluruh ponsel dan tablet. |
| 5 | Error Prevention | 2 | `.news-card{overflow:hidden}` tanpa `overflow-wrap` memotong judul kapital warisan di tengah huruf - "PENGUMUM|" terpenggal di tepi kartu 390px, tanpa elipsis. Konten dihancurkan, bukan dipangkas. |
| 6 | Recognition Rather Than Recall | 3 | Dateline, waktu baca, dan TOC berhitung bagian benar-benar membantu. Tapi "Berita terkait" janji yang tidak bisa ditepati perankingnya - hasilnya tiga berita terbaru tanpa kaitan. |
| 7 | Flexibility and Efficiency | 2 | Tiga perhentian tab per kartu (media / judul / "Baca berita") untuk satu URL - 18 perhentian untuk 6 artikel. |
| 8 | Aesthetic and Minimalist Design | 1 | Di 1440x900 judul berita pertama ada di y=1227: nol judul di layar pertama. Kata "berita" muncul 16 kali di halaman itu. Di 390px satu kartu setinggi 450px dengan judul 10 baris di kolom 146px, di samping potongan 126px foto lanskap. |
| 9 | Error Recovery | 1 | Status kosong tidak menawarkan jalan keluar - tanpa pencarian, tanpa daftar tahun; `?start=996` mencetak "Page 14 of 14" di atas nol artikel. |
| 10 | Help and Documentation | 3 | Panel layanan dengan `tel:` dan WhatsApp adalah bantuan nyata di tempat yang tepat. Relevansinya meleset: artikel pelantikan pegawai menawarkan "Zona Integritas" di bawah judul "Untuk pencari keadilan". |
| **Total** | | **20/40** | **Di bawah rata-rata - templat artikel yang kuat dinikahkan dengan halaman daftar yang belum selesai** |

## Design Specificity Verdict

**Halaman artikel: diotori untuk pengadilan. Halaman daftar: bisa dipakai produk mana pun.**

Copot maroon dan lambangnya dari `/berita`, yang tersisa adalah indeks blog CMS 2 kolom generik: hero gedung dengan judul overlay, dua tab pil, grid kartu foto -> tanggal -> label -> judul -> kutipan -> "Baca berita", lalu pagination bernomor. Tidak ada satu pun keputusan komposisi yang tahu bahwa yang didaftar adalah urusan pengadilan.

Halaman artikel justru sebaliknya dan benar-benar bagus: kop berlambang di atas garis emas 2px, dateline `NATUNA, 1 Juni 2026 —`, meta berinterpunkt emas, rel daftar isi menggantung di margin kiri tanpa menggeser kolom baca. Itu komposisi yang tidak bisa dipakai produk lain. Medium membuka dengan wajah penulis dan tombol Follow; menirunya di sini akan membuat pernyataan kelembagaan tampak seperti opini pribadi.

Jadi masalahnya bukan "terlalu generik", melainkan **keyakinan yang tidak merata**: halaman artikel berkomitmen, halaman daftar menyerah pada bawaan - dan halaman daftar itulah yang dilewati setiap pembaca lebih dulu.

Lebih dalam lagi: kerapian artikel itu **kosmetik pada 93% korpus**. Hanya 12 dari 177 foto badan artikel duduk di dalam bingkai `editorial-article__figure`; 165 sisanya `<img>` telanjang. Enam artikel pernah direstrukturisasi dengan tangan - dan ketiga contoh yang biasa dipakai untuk menilai ada di antara keenamnya. Artikel yang khas bukan artikel yang pernah ditinjau.

**Pindaian deterministik.** `detect.mjs` atas kedua templat: 3 temuan `broken-image` (`article/default.php:210,231,237`) - **ketiganya positif palsu**: dua adalah pola regex `preg_replace_callback` yang justru mencari `<img>`, satu ada di dalam komentar. Pindaian direktori markup penuh: 0 temuan, exit 0. Detektor dalam-peramban melaporkan agregat 20 anti-pola di `/berita` dan 10 di tiap artikel tanpa memerinci elemen, jadi tidak bisa ditautkan ke cacat tertentu.

**Dua temuan Assessment B gugur setelah saya uji ulang:** (1) "5 tautan tanpa nama aksesibel" - hasil pengukuran ulang: **0**; penghitung nama milik B tidak membaca `aria-label` dan `alt`. (2) "target sentuh di bawah 44px" pada AMPUH/PTSP/telepon/WhatsApp - seluruhnya `display:inline` di dalam kalimat, yang dikecualikan WCAG 2.2 SC 2.5.8. Kontras aman di dua mode: terendah 6,02:1 (meta kartu terang), badan artikel 12,07:1 terang / 12,73:1 gelap.

## Overall Impression

Tipografinya sudah sekelas Medium; yang belum sekelas adalah **kejujuran halamannya**. Tiga cacat yang tidak akan pernah dikirim Medium: foto utama dicetak dua kali pada 75 dari 81 artikel, seluruh 93 kapsi berupa boilerplate yang bahkan bertentangan tanggal dengan dateline artikelnya sendiri, dan judul yang terpotong di tengah huruf di ponsel. Peluang terbesar bukan menyerupai Medium lebih jauh - melainkan menyelesaikan halaman daftar dengan standar yang sudah dibuktikan halaman artikel.

## What's Working

1. **Kop / dateline / meta.** Gradien otoritas menurun - lembaga, subjek, provenans - persis cara komunikasi resmi dibaca, dan emas hanya muncul sebagai garis, tidak pernah sebagai teks. Ini yang seharusnya dicontek Medium, bukan sebaliknya.
2. **Rel daftar isi.** Dirender server-side (jangkar bekerja tanpa JS), hanya muncul bila bagian > 1, menggantung di x=96,8 pada 1200px **tanpa menggeser kolom baca** (pusat badan = 600 = pusat halaman). Komentar di css:12418 mencatat ini percobaan kedua setelah yang pertama menggeser kolom 128px. Standar inilah yang belum dipakai di halaman daftar.
3. **Bobot halaman.** `/berita` 79 KB di 390px, hero artikel 64 KB dari sumber 5.091 KB. Untuk pengguna kepulauan ini fitur, bukan angka teknis.

## Priority Issues

### [P0] Foto utama dicetak dua kali pada 75 dari 81 artikel
**Apa.** Audit saya sendiri atas seluruh 84 artikel Berita: 81 punya hero, dan pada **75 di antaranya berkas yang sama persis muncul lagi di dalam badan artikel** - tanpa bingkai, tanpa kapsi. Contoh terbukti: `/berita/upacara-hari-lahir-pancasila-2026` mencetak `2026-harlah-pancasila-1.jpeg` di y=611 (860px, berkapsi) lalu lagi di y=1618 (738px, telanjang). Empat artikel juga menggandakan satu paragraf utuh.
**Kenapa penting.** Ini cacat kerajinan terbesar di halaman artikel, dan tidak terlihat dari tiga artikel contoh - ketiganya termasuk enam yang pernah dirapikan tangan. Pembaca bertemu foto yang sama dua kali dalam satu layar gulir; reaksinya "apakah halaman ini rusak", tepat di permukaan tempat kredibilitas lembaga dibangun.
**Perbaikan.** Di `article/default.php`, setelah pass srcset, buang dari `$articleBody` setiap `<img>` yang basename-nya sama dengan hero, beserta `<p>` pembungkus bila jadi kosong. Kunci dengan kontrak baru.
**Perintah:** `/impeccable polish`

### [P0] Kartu ponsel memotong judul di tengah huruf dan meremas foto jadi serpihan
**Apa.** Di 390px `.news-card` menjadi `grid-template-columns:126px minmax(0,1fr)` (css:12576). Terukur: kolom teks 130-192px, judul 23px Fraunces **tanpa clamp**, kartu 279-450px. "Kegiatan Puncak HUT IKAHI yang Ke-70" pecah jadi satu-dua kata per baris; "PENGUMUMAN PENETAPAN DAN PEMENANG POSBAKUM 2026" **terpenggal di tengah kata** oleh `overflow:hidden` tanpa elipsis. Fotonya jadi bilah 126x448 - potongan 1:3,5 dari foto lanskap yang isinya tak terbaca lagi.
**Kenapa penting.** Ini satu-satunya tampilan indeks berita bagi persona utama, dan kehilangan konten di 390px melanggar WCAG 2.2 SC 1.4.10 (Reflow). Medium di 390px: gambar 342x171 selebar kolom, judul 20px/24px **di-clamp tepat 2 baris**, subjudul 16px clamp 2, kartu sekitar 340px.
**Perbaikan.** Di bawah 760px: lepas padding `.content-primary`/`.news-channel` persis seperti yang sudah dilakukan artikel di css:12437 (memulihkan 106px jadi 358px); tumpuk kartunya - foto selebar kolom di atas teks pada rasio 3:2; `-webkit-line-clamp:3` + `overflow-wrap:anywhere` pada `.news-card h2`. **Ambil dari Medium: line clamp-nya. Jangan ambil: kartu foto 2:1 bergaya lifestyle - 3:2 dokumenter yang benar untuk pengadilan.**
**Perintah:** `/impeccable adapt`

### [P1] Seluruh 93 kapsi boilerplate, sementara kapsi sungguhan menganggur di `alt`
**Apa.** 93 dari 93 `<figcaption>` berbunyi "Dokumentasi Pengadilan Negeri Natuna - <tanggal>" (`article/default.php:202,223`). Lebih buruk: tanggal itu tanggal terbit, sehingga di artikel Harlah Pancasila kapsinya menulis **3 Juni 2026** tepat di bawah dateline artikelnya sendiri yang berbunyi **1 Juni 2026**. Sementara itu `alt`-nya spesifik dan bagus: *"Pemaparan materi penyampaian panggilan lewat surat tercatat oleh Panitera PN Natuna dan perwakilan PT Pos Indonesia"* - hanya dibacakan untuk pengguna pembaca layar.
**Kenapa penting.** Informasinya sudah ada, sudah di database, dan disembunyikan dari pembaca awas. Kapsi yang berulang berhenti dibaca setelah instansi pertama, jadi efektifnya foto-foto itu tidak berkapsi. Ini justru sifat Medium yang paling layak diambil: kapsinya *menjelaskan fotonya*.
**Perbaikan.** Di callback figur, pakai `alt` sebagai kapsi bila ada dan bukan sekadar judul artikel; turunkan kredit jadi sufiks atau tempel sekali di figur terakhir; jatuh ke teks lama hanya bila `alt` kosong. Perbaiki juga tanggalnya jadi tanggal peristiwa, bukan tanggal terbit.
**Perintah:** `/impeccable clarify`

### [P1] Tombol "Bagikan artikel" mati di Chrome dan Firefox desktop
**Apa.** `template.js:1922-1926` menyembunyikan tombol dengan `nativeButton.hidden = true` lalu `return` tanpa memasang listener. Tapi `.editorial-article__share button{display:inline-flex}` (css:12371) mengalahkan `[hidden]{display:none}` bawaan peramban. Dibuktikan langsung di halaman: `hidden` diset -> computed `display:flex`, kotak 147x44, tetap bisa diklik, dan setelah diklik status `aria-live` tetap kosong. Ada 13 komponen lain di CSS ini yang sudah menambahkan aturan `[hidden]`-nya sendiri - jebakan ini sudah dikenal tim, hanya terlewat di sini.
**Kenapa penting.** Chrome dan Firefox desktop tidak punya `navigator.share`. Pengguna menekan tombol utama dan tidak terjadi apa-apa - tanpa pesan gagal, tanpa cara tahu apakah salah klik.
**Perbaikan.** Tambahkan `.editorial-article__share button[hidden]{display:none}`. Satu baris.
**Perintah:** `/impeccable harden`

### [P1] Cincin fokus daftar gagal pada aturan yang sudah dipecahkan artikel
**Apa.** `.news-channel :focus-visible{outline:3px solid #d5a530}` (css:12571) dan `.news-portal a:focus-visible` (css:12502) - **2,25:1** di atas kartu `#FFFDF9`, di bawah ambang 3:1 SC 1.4.11. Obatnya sudah ditulis tim sendiri satu blok di atasnya, lengkap dengan diagnosisnya: css:12384 mencatat *"#d1a42f hanya 2,32:1 di atas kartu putih; SC 1.4.11 menuntut 3:1"* lalu css:12386 memakai `#7a4b00` + halo emas.
**Kenapa penting.** WCAG 2.2 AA dinyatakan sebagai kontrak, dan ini cacat yang sudah diketahui, sudah dipecahkan, hanya tidak disebarkan. Pengguna keyboard di halaman daftar bisa sama sekali tidak melihat indikatornya.
**Perbaikan.** Pakai cincin artikel untuk kedua selektor itu. Satu baris masing-masing.
**Perintah:** `/impeccable audit`

### [P2] Ponsel dan tablet mendapat tipografi terkecil di situs
**Apa.** Terukur di lima lebar layar:

| Layar | Badan | Tinggi baris | Kolom | Karakter/baris |
|---|---|---|---|---|
| 390 | 16px | 27,2 | 358 | 39 |
| 768 | 16px | 27,2 | **640** | **70** |
| 1024 | 16px | 27,2 | 640 | 70 |
| 1200 | 18px | 30,6 | 648 | 63 |
| 1440 | 20px | 34 | 760 | 66 |

`clamp(1rem,1.5vw,1.25rem)` tidak pernah meninggalkan lantainya sampai 1,5vw melewati 16px, yaitu di 1067px. Pita 768-1024 adalah yang terburuk: **teks 16px direntangkan sampai 70 karakter per baris** - huruf kecil dengan ukuran baris terpanjang di seluruh situs. Medium memberi 20px/32px pada desktop dan tidak pernah turun ke 16px pada tablet.
**Kenapa penting.** PRODUCT.md menyebut pembaca ponsel sebagai persona utama; merekalah yang justru menerima tipografi terkecil.
**Perbaikan.** Naikkan lantai ke 17-18px dan potong measure-nya di pita tablet (`--editorial-measure` maksimum ~62-66 karakter). Jangan sentuh desktop - 20px/760px sudah pas.
**Perintah:** `/impeccable typeset`

### [P2] 1.227px sebelum judul berita pertama, dan kata "berita" 16 kali
**Apa.** Terukur di 1440x900: h1 di y=426, tab di y=705, gambar kartu pertama di y=827, **judul berita pertama di y=1227** - nol judul di layar pertama. Kicker bilang BERITA TERKINI, h1 bilang Berita Pengadilan Negeri Natuna, paragraf pengantar mengulanginya, tab aktif mengulanginya, dan label meta tiap kartu bilang BERITA di kanal Berita - label itu satu-satunya elemen emas di baris meta, jadi ia merebut mata sebelum tanggalnya. Kata "berita" muncul 16 kali. Medium menaruh gambar kartu pertamanya di y sekitar 340.
**Kenapa penting.** Indeks berita yang tugasnya menunjukkan berita tidak menunjukkan satu pun di layar pertama.
**Perbaikan.** Separuhkan hero-nya atau ganti dengan pita masthead teks memakai garis emas artikel; hapus paragraf pengantar; sembunyikan label kanal saat ia sama dengan kanalnya. Pertahankan tab - dua kanal itu IA pengadilan yang sah.
**Perintah:** `/impeccable layout`

## Persona Red Flags

**Ibu Rina - warga biasa, Android, sinyal kepulauan tidak stabil, mencari pengumuman Posbakum.** Mendarat di `/berita` 390px: layar pertama habis untuk hero dan tab, judul berita pertama baru di y=620. Kartu pertama menampilkan judul terpenggal "PENGUMUM|" di tepi kartu. Ia menggulir enam kartu sejauh 3.775px, tiba di pagination, dan disodori **14 nomor halaman tanpa tahun, tanpa bulan, tanpa pencarian** - pengumumannya bertanggal Desember 2025 dan tidak ada yang memberitahu itu ada di halaman 12. Begitu artikel dibuka, **165 dari 177 foto badan tidak punya `width`/`height`**, jadi paragraf yang sedang ia baca melompat turun setiap satu foto tiba di koneksinya yang lambat.
*Elemen yang mengecewakannya:* `.news-card` di <=760px; `.news-channel-pagination` tanpa faset tanggal; 165 gambar tanpa dimensi.

**Pak Herman - advokat, desktop, keyboard saja.** Menab masuk daftar dan mendapat **18 perhentian untuk 6 artikel** (media / judul / "Baca berita", satu URL), di bawah cincin fokus 2,25:1 yang mungkin tidak terlihat di layar terang. Di kaki artikel ia menekan "Bagikan artikel" - di Chrome desktop tombolnya tetap tampak, tidak punya listener, dan tidak terjadi apa-apa. Tidak ada yang membedakan artikel yang sudah ia baca: tidak ada aturan `:visited` di seluruh stylesheet.

**Bu Sari - staf humas, menerbitkan kegiatan besok.** Ia mengunggah satu foto ke kolom intro lalu menempelkan foto yang sama di badan - persis seperti 75 artikel sebelumnya - dan halaman mencetaknya dua kali tanpa peringatan. Ia menulis `alt` yang menjelaskan adegannya, dan templat membuangnya demi "Dokumentasi Pengadilan Negeri Natuna - 2 Agustus 2026". Kalau warga menggulir melewati halaman terakhir, yang terbaca kalimat Inggris Joomla.

## Minor Observations

- **Truncation empat titik** di kartu paling atas: `…pada Senin, 27 Juli 2026....` - `string.truncate` menambahkan `...` pada kalimat yang sudah bertitik (`blog_item.php:68`). Pangkas tanda baca dulu, lalu pakai `…`.
- **"Baca berita" adalah tautan ketiga ke tujuan yang sudah ditautkan dua kali**, dan `min-height:32px` di desktop - di bawah kontrak 44px situs ini sendiri. Hapus saja; kartu Medium tidak punya CTA.
- **Dua pengumuman Posbakum tayang ganda** - satu di kanal Berita berawalan `legacy-`, satu di kanal Pengumuman, judul identik. Satu di antaranya ditulis KAPITAL SEMUA dan dicap "BERITA".
- **CSS mati terkirim di tiap halaman.** `.news-listing--announcement-cards` dipancarkan `blog.php:74` tanpa satu pun aturan; blok `css:2487-2600` sudah digantikan `css:12553+`. Di koneksi kepulauan, byte adalah fitur.
- **Galeri 3 kolom dibangun untuk satu artikel.** `.editorial-article__gallery` menyala pada **1 dari 84** artikel karena regex-nya menuntut figur berurutan tanpa paragraf di antaranya.
- **Kartu pengumuman memotong pindaian dokumen A4** ke bingkai lanskap 529x353 sehingga isinya tak terbaca; `.editorial-article__hero--document` dengan `object-fit:contain` sudah ada dan seharusnya dipakai kartu.
- **`?start=996` mencetak "Page 14 of 14"** di atas nol artikel.
- **Tidak ada penutup manusiawi di ujung artikel.** Setelah kalimat terakhir: panel layanan, satu baris kelabu "Diterbitkan oleh Pengadilan Negeri Natuna", tiga tombol bagikan, tiga kartu terkait, satu tautan kembali - sekitar 1.500px perancah tanpa satu momen penutup. Medium bekerja di titik ini karena ada orang berdiri di sana. Pengadilan tidak boleh menawarkan tombol Follow, tapi bisa menandatangani: unit humas, satu nama, satu baris "hubungi kami tentang berita ini".

## Questions to Consider

1. Kalau halaman artikel percaya publikasi pengadilan harus tampak seperti siaran pers, kenapa halaman daftar percaya ia harus tampak seperti blog? Bagaimana wujud `/berita` bila dirancang sebagai **arsip siaran pers** - entri bertanggal di bawah judul tahun, berita utama diberi bobot nyata - alih-alih grid berbobot sama tempat apel pagi 2023 setara penilaian AMPUH bulan ini?
2. Siapa yang menandatangani berita sebuah pengadilan?
3. Apakah "Berita terkait" janji yang bisa ditepati? Medium menulis "More from <publikasi>" dan jujur. Apa ruginya "Berita terbaru lainnya"?
4. Foto-foto itu sudah punya kapsi bagus - di dalam `alt`. Apa lagi di sistem ini yang dijelaskan dengan benar di tempat yang tidak bisa dilihat pembaca?
5. Empat belas halaman berisi enam. Apa di antarmuka ini yang menolong warga mencari pengumuman Desember 2025? Apakah indeks tahun di margin - pola marginalia yang sudah terbukti bekerja pada rel TOC - tidak lebih berguna daripada sepuluh tombol bernomor?
6. Sifat Medium mana yang justru penurunan mutu bagi pengadilan? Follow, clap, gerbang anggota, hero berbasis kepribadian, gulir tak berujung yang merusak ketertautan - semuanya salah di sini. Judul ber-clamp, kapsi deskriptif, label yang jujur, dan jarak pendek ke judul pertama - semuanya benar.
