# Pengumuman BMN dan Ukuran Panel Fasilitas

**Tanggal:** 16 Juli 2026  
**Status:** Disetujui untuk perencanaan implementasi

## Tujuan

Mengimpor dua pengumuman lelang BMN terbaru dari homepage lama PN Natuna ke kanal Pengumuman lokal, sekaligus memperbesar panel dokumenter pada tiga halaman fasilitas tanpa mengubah Galeri Fasilitas homepage.

## Sumber pengumuman

Homepage sumber: `https://www.pn-natuna.go.id/index.php/en/`

Item baru yang belum ada pada kategori Pengumuman lokal:

1. **Pengumuman Penetapan Pemenang Lelang BMN**  
   Dokumen: `https://drive.google.com/file/d/1KDGdzwbuK0Wbqu_3MlbjHTrpDgpl3Td6/view?usp=sharing`  
   Nama file publik: `penetapan pemenag lelang.pdf`
2. **Pengumuman Lelang BMN Pada Pengadilan Negeri Natuna**  
   Dokumen: `https://drive.google.com/file/d/1E4v21cQPCrXDP6F3rXNZWCWeobzmRB-s/view?usp=sharing`  
   Nama file publik: `pengumuman lelang.pdf`

Dua pengumuman Posbakum tidak diimpor ulang karena sudah ada secara lokal.

## Tanggal publikasi

Kedua dokumen memiliki tanggal resmi yang dapat diverifikasi dari halaman PDF: **Pengumuman Lelang** bertanggal **4 Juni 2026** pada halaman kedua, sedangkan **Penetapan Pemenang Lelang** bertanggal **11 Juni 2026** pada halaman pertama. Artikel lokal memakai tanggal resmi tersebut; tanggal pengambilan **16 Juli 2026** hanya dicatat sebagai provenance dalam metadata/isi, bukan tanggal publikasi.

## Bentuk artikel lokal

Kedua item masuk kategori Pengumuman ID 13 sebagai artikel published dan public. Masing-masing berisi:

- judul resmi;
- keterangan bahwa dokumen berasal dari homepage resmi PN Natuna;
- tombol/link **Buka Dokumen Resmi** menuju Google Drive;
- tautan eksternal `target="_blank" rel="noopener noreferrer"`;
- sumber homepage lama;
- tanpa menyalin atau mengarang isi PDF yang belum diekstrak.

Alias:

- `pengumuman-penetapan-pemenang-lelang-bmn`
- `pengumuman-lelang-bmn-pengadilan-negeri-natuna`

Deduplikasi dilakukan berdasarkan alias dan URL dokumen. Migrasi SQL baru wajib idempoten dan tidak mengubah artikel lama. Showcase **Pengumuman Baru** otomatis mengambil keduanya berdasarkan tanggal terbaru.

## Thumbnail dokumen

Kedua artikel tidak memakai fallback kategori yang sama. PDF resmi diunduh dari Google Drive, diverifikasi sebagai PDF, lalu halaman pertama masing-masing dirender menjadi WebP:

- `images/pengumuman/bmn-penetapan-pemenang-lelang-2026.webp`
- `images/pengumuman/bmn-pengumuman-lelang-2026.webp`

Thumbnail memakai rasio konsisten, latar dokumen utuh, dan pemrosesan hanya rasterisasi serta optimasi WebP. Tidak mengubah, menambah, menutup, atau menyusun ulang isi dokumen. Artikel menyimpan thumbnail sebagai `image_intro` dan `image_fulltext` bila sesuai pola Joomla lokal, sehingga feature dan compact showcase memiliki visual berbeda. PDF asli tidak wajib disimpan dalam repository karena artikel tetap menautkan dokumen Drive kanonis; cache unduhan bersifat sementara dan tidak dilacak Git.

## Panel dokumenter halaman detail

Galeri Fasilitas homepage tidak berubah.

### `/jenis-layanan-ptsp`

- Desktop: tinggi `380px`.
- Mobile: tinggi `230px`.
- `object-fit: contain` agar kelima petugas tetap terlihat.
- Latar media gelap netral mengisi ruang kosong.

### `/layanan-disabilitas`

- Desktop: tinggi `350px`.
- Mobile: tinggi `220px`.
- `object-fit: cover` dengan posisi objek yang mempertahankan kursi roda sebagai subjek utama.

### `/posbakum`

- Desktop: tinggi `360px`.
- Mobile: tinggi `220px`.
- Gunakan `contain` bila `cover` memotong meja layanan; keputusan final berdasarkan inspeksi browser 1366×768 dan 390×844.

Caption, lightbox, alt, mode gelap, focus keyboard, dan reduced-motion tetap. Isi layanan tidak berubah.

## Implementasi

- Tambahkan class varian pada figure disabilitas dan Posbakum melalui migrasi idempoten baru atau migrasi konten baru yang dijaga exact alias dan hash pra-perubahan.
- Jangan mengedit migrasi fasilitas yang sudah tercatat.
- Tambahkan CSS varian per halaman; jangan mengubah `.facility-thumb` homepage.
- Tambahkan migrasi pengumuman terpisah agar konten dan styling dapat diaudit independen.

## Verifikasi

Kontrak otomatis membuktikan:

- dua alias pengumuman dan dua URL Drive tepat;
- dua thumbnail WebP berasal dari halaman pertama PDF yang berbeda dan lolos pemeriksaan MIME/dimensi;
- `image_intro` kedua artikel mengarah ke thumbnail masing-masing, bukan fallback kategori;
- kategori 13, published, public, dan deduplikasi idempoten;
- tanggal tidak diklaim sebagai tanggal dokumen resmi;
- link eksternal aman;
- Galeri homepage CSS dan markup tidak berubah;
- tinggi desktop/mobile per halaman sesuai;
- PTSP mempertahankan `contain`;
- konten layanan dan infografik PTSP tetap.

Smoke test Chromium:

- `/pengumuman` menampilkan dua item BMN paling baru tanpa duplikasi;
- showcase homepage menampilkan pengumuman BMN terbaru;
- kedua link Drive terbuka;
- tiga halaman fasilitas tidak overflow dan framing subjek baik;
- lightbox, focus, serta mode gelap tetap bekerja;
- Galeri Fasilitas homepage tetap empat kartu dengan ukuran lama.

## Non-sasaran

- Tidak mengubah isi PDF atau mengklaim tanggal yang tidak tersedia.
- Tidak mengimpor berita terbaru selain dua pengumuman BMN.
- Tidak memperbesar thumbnail Galeri Fasilitas homepage.
- Tidak mengubah syarat, alur, kontak, atau fakta layanan.
