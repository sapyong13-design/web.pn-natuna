---
target: halaman berita /berita dan halaman artikel
total_score: 30
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 3
timestamp: 2026-08-01T17-16-17Z
slug: atuna-2026-html-com-content-category-blog-item-php
---
⚠️ DEGRADED: single-context (re-score of verified deltas, not a fresh dual-agent critique). Every heuristic below was re-checked against the live site by the parent in this session; no score was carried over on assumption.

## Design Health Score

| # | Heuristik | Dulu | Kini | Bukti keadaan sekarang |
|---|-----------|------|------|------------------------|
| 1 | Visibility of System Status | 2 | 3 | Tombol bagikan hidup dan menyembunyikan diri dengan benar; status `aria-live` berfungsi. Masih nol aturan `:visited` untuk arsip 84 item - pembaca tidak bisa tahu mana yang sudah dibuka. |
| 2 | Match System / Real World | 2 | 3 | Status kosong berbahasa Indonesia dengan jalan keluar. 28 dari 93 kapsi kini menjelaskan fotonya; 65 sisanya terhalang isi, bukan kode (68 dari 84 `alt` berisi judul artikel). Judul "Berita di sekitar tanggal ini" mengatakan apa yang benar-benar ditampilkan. |
| 3 | User Control and Freedom | 3 | 3 | Tautan kembali tetap presisi ke halaman daftar asal. Tidak berubah: 14 halaman tanpa faset tahun/bulan dan tanpa pencarian kanal. |
| 4 | Consistency and Standards | 1 | 3 | Cincin fokus seragam di seluruh permukaan (7,29:1); tangga tipografi menyambung; daftar melepas padding di <=760px seperti artikel. Sisa ketidakseragaman nyata: tiga rasio foto di satu permukaan - kartu daftar 3:2, kartu terkait 16:9 di desktop dan 4:3 di ponsel. |
| 5 | Error Prevention | 2 | 3 | `-webkit-line-clamp` + `overflow-wrap:anywhere` menghentikan pemenggalan di tengah huruf; SC 1.4.10 Reflow lulus. Pagination masih mengizinkan pembaca mencapai halaman yang tidak ada. |
| 6 | Recognition Rather Than Recall | 3 | 4 | Berita terkait kini jujur dan bervariasi: 17 -> 70 artikel unik, kemunculan tertinggi 54x -> 7x, dan judulnya berubah jadi "Berita di sekitar tanggal ini" ketika kaitannya memang tidak ada. Dateline, waktu baca, dan TOC berhitung bagian tetap membantu. |
| 7 | Flexibility and Efficiency | 2 | 2 | Tidak tersentuh. Terukur ulang: 18 perhentian tab untuk 6 artikel - tiga tautan per kartu (media, judul, "Baca berita") menuju satu URL. Pembaca layar mendengar tiap judul tiga kali. |
| 8 | Aesthetic and Minimalist Design | 1 | 3 | Hero 280 -> 123px, paragraf pengantar dihapus, label kanal berulang disembunyikan, kartu ponsel dibangun ulang, ekor artikel 22% -> 17%. Judul pertama 1227 -> 1041 - tetapi garis lipat 900px, jadi masih nol judul di layar pertama pada 1440. |
| 9 | Error Recovery | 1 | 3 | `?start=996` kini menampilkan "Belum ada artikel pada halaman ini. Kembali ke halaman pertama berita." Masih mencetak "Page 14 of 14" di atas nol artikel. |
| 10 | Help and Documentation | 3 | 3 | Tidak tersentuh. Panel layanan masih meleset relevansinya: artikel pelantikan pegawai menawarkan "Zona Integritas" di bawah judul "Untuk pencari keadilan". |
| **Total** | | **20/40** | **30/40** | **Baik - di atas rata-rata; sisa masalahnya navigasi arsip, bukan kerajinan halaman** |

## Yang berubah sejak skor pertama

Delapan cacat ditutup, seluruhnya diverifikasi ulang dengan audit korpus atau pengukuran langsung:

| | Sebelum | Sesudah |
|---|---|---|
| Foto hero tercetak dua kali | 75 dari 81 | 0 |
| Paragraf tercetak ganda | 4 artikel | 0 |
| Foto badan tanpa `width`/`height` | 165 dari 177 | 0 |
| Kapsi deskriptif | 0 dari 93 | 28 |
| Artikel unik di "Berita terkait" | 17 dari 84 | 70 dari 84 |
| Kemunculan terbanyak satu artikel | 54 halaman | 7 halaman |
| Tautan WhatsApp per artikel | 3 (2 identik) | 1 kontak + 1 aksi bernama |
| Kolom teks kartu di 390px | 130-192px | 322px |
| Judul terpenggal di tengah huruf | ya | tidak |
| Cincin fokus daftar | 2,23:1 | 7,29:1 |
| Bobot `/berita` di ponsel | 357 KB | 139 KB |
| Judul berita pertama di 1440 | y=1227 | y=1041 |
| Ekor artikel panjang / pendek | 22% / 42% | 17% / 39% |

## Sisa ruang perbaikan, urut nilai

### [P1] Tiga tautan per kartu untuk satu tujuan
18 perhentian tab untuk 6 artikel, dan pembaca layar mendengar setiap judul tiga kali. "Baca berita" adalah tautan ketiga ke tempat yang sudah ditautkan foto dan judulnya. **Perbaikan:** hapus "Baca berita", jadikan seluruh kartu satu tautan dengan judul sebagai nama aksesibelnya. Murah, dan langsung memotong perhentian tab dari 18 jadi 6.

### [P1] Tidak ada tanda artikel yang sudah dibaca
Nol aturan `:visited` di seluruh stylesheet, untuk arsip 84 item yang tersebar di 14 halaman. Warga yang mencari satu pengumuman menelusuri halaman demi halaman tanpa jejak. **Perbaikan:** satu aturan `:visited` pada judul kartu.

### [P1] Empat belas halaman berisi enam, tanpa faset
Cacat navigasi terbesar yang tersisa. Pengumuman Desember 2025 ada di halaman 12 dan tidak ada satu pun elemen yang memberi tahu itu. **Perbaikan:** indeks tahun di margin, memakai pola marginalia yang sudah terbukti bekerja pada rel daftar isi di atas 1200px; atau naikkan jumlah per halaman.

### [P2] Panel layanan salah sasaran
Artikel pelantikan pegawai menawarkan "Zona Integritas" di bawah judul "Untuk pencari keadilan". Panel dipicu kata di badan tulisan tanpa memeriksa apakah artikelnya memang berurusan dengan pencari keadilan. **Perbaikan:** hanya tampilkan panel bila kanal layanannya cocok dengan subjek artikel, bukan sekadar kata yang lewat.

### [P2] Pagination mengizinkan halaman yang tidak ada
`?start=996` mencetak "Page 14 of 14" di atas nol artikel.

### [P2] Tiga rasio foto di satu permukaan
Kartu daftar 3:2, kartu terkait 16:9 di desktop, 4:3 di ponsel. Satu bahasa foto akan lebih tenang.

### [P3] Tidak ada penutup manusiawi
Setelah kalimat terakhir tidak ada seorang pun yang berdiri di sana - hanya panel layanan dan kartu. Pengadilan tidak boleh menawarkan tombol Follow, tapi bisa menandatangani: unit humas, satu nama, satu baris "hubungi kami tentang berita ini". Butuh keputusan pemilik soal nama dan nomor yang boleh dipublikasikan.

### Bukan pekerjaan kode
- **68 dari 84** `alt` foto hero berisi judul artikel, bukan keterangan foto. Ini menahan 65 kapsi tetap generik, dan juga berarti pembaca layar tidak mendapat keterangan apa pun. Begitu redaksi mengisi `alt` dengan deskripsi adegan, kapsinya ikut membaik tanpa sentuhan kode.
- Dua pengumuman Posbakum tayang ganda di kanal Berita dan Pengumuman.
