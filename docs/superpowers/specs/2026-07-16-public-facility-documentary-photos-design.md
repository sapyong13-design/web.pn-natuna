# Pembaruan Foto Dokumenter Fasilitas Publik

**Tanggal:** 16 Juli 2026  
**Status:** Disetujui untuk perencanaan implementasi

## Tujuan

Memperbarui presentasi Ruang PTSP, Akses Disabilitas, dan Posbakum dengan dokumentasi fasilitas terbaru yang konsisten, sinematik, cepat dimuat, dan tetap faktual.

## Ruang lingkup

- Mengolah foto PTSP baru dari pengguna menjadi WebP sinematik teroptimasi.
- Memperbarui kartu Ruang PTSP pada modul Galeri Fasilitas Publik.
- Menambahkan panel dokumenter lebar pada `/jenis-layanan-ptsp`.
- Mengganti foto lama pada `/layanan-disabilitas` dan `/posbakum` dengan aset WebP terbaru dari galeri.
- Menyeragamkan bingkai, caption, lightbox, mode gelap, focus keyboard, dan responsivitas ketiga halaman.
- Mempertahankan isi layanan, syarat, alur, kontak, serta struktur navigasi.

## Aset

### Ruang PTSP

Sumber adalah foto 1568×1045 yang diberikan pengguna pada percakapan ini. Hasil kanonis:

`images/layanan/gallery/ruang-ptsp-2026.webp`

Pengolahan hanya fotografis:

- crop lebar mempertahankan lambang PN Natuna, tulisan Pelayanan Terpadu Satu Pintu, lima petugas, meja, dan konteks ruang;
- koreksi exposure, white balance, contrast, highlight, shadow, dan ketajaman ringan;
- tidak mengubah wajah, seragam, tulisan, benda, atau isi dokumentasi;
- output WebP dengan lebar memadai untuk panel desktop dan ukuran file teroptimasi.

Alt: **Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna**

Caption: **Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna siap melayani masyarakat.**

### Akses Disabilitas

Aset kanonis:

`images/layanan/gallery/akses-disabilitas-2026.webp`

Alt: **Kursi roda dan alat bantu mobilitas di Pengadilan Negeri Natuna**

Caption: **Kursi roda dan alat bantu mobilitas yang tersedia untuk pengguna layanan prioritas.**

### Posbakum

Aset kanonis:

`images/layanan/gallery/posbakum-2026.webp`

Alt: **Meja layanan Pos Bantuan Hukum Pengadilan Negeri Natuna**

Caption: **Meja layanan Pos Bantuan Hukum di area PTSP Pengadilan Negeri Natuna.**

## Penempatan halaman

### `/jenis-layanan-ptsp`

Panel dokumenter ditempatkan setelah pembuka dan informasi jam layanan, sebelum daftar Layanan per Kepaniteraan. Foto menjadi bukti ruang dan petugas, bukan pengganti infografik `biaya-jenis-layanan.png` atau `waktu-layanan.png` yang tetap dipertahankan.

### `/layanan-disabilitas`

Panel Fasilitas Akses yang sekarang memakai `images/layanan/akses-disabilitas.jpg` dipindahkan ke posisi setelah pembuka layanan dan diganti aset WebP terbaru. Bagian prinsip, sarana, pendampingan, dan kontak tetap.

### `/posbakum`

Panel dokumenter ditempatkan setelah pembuka, lokasi, dan jam layanan, sebelum Apa yang Bisa Anda Peroleh. Foto lama `images/layanan/posbakum.jpg` diganti aset terbaru. Bagian manfaat, persyaratan, alur, dan CTA tetap.

## Komponen visual bersama

Ketiga halaman memakai pola `.facility-documentary`:

- figure lebar, bukan kartu kecil;
- media rasio sinematik desktop sekitar 16:7 sampai 16:9;
- `object-fit: cover` dengan `object-position` per foto agar subjek utama tidak terpotong;
- caption ringkas di bawah media;
- tautan media membuka lightbox yang sudah tersedia;
- focus-visible jelas;
- latar, border, caption, dan kontrol terbaca dalam mode gelap;
- mobile memakai rasio lebih tinggi dan object-position khusus bila diperlukan;
- gambar lazy-loaded dan asynchronous decoding karena berada setelah pembuka.

Tidak ada autoplay, carousel, modal baru, atau efek parallax. Motion hanya zoom gambar ringan pada hover/focus dan mengikuti reduced-motion guard proyek.

## Galeri homepage

Modul Galeri Fasilitas Publik ID 480 tetap empat kartu. Hanya kartu Ruang PTSP berubah dari foto briefing lama ke `images/layanan/gallery/ruang-ptsp-2026.webp`. Kartu Akses Disabilitas dan Posbakum sudah memakai aset terbaru dan tidak berubah. Alt kartu PTSP menjadi **Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna**.

Perubahan isi Joomla harus berupa migrasi SQL idempoten baru di `database/migrations/`. Migrasi lama `20260713_facility_gallery_photos.sql` tidak diedit.

## Verifikasi

Kontrak otomatis:

- migrasi baru menargetkan modul ID 480 secara sempit;
- tiga route mereferensikan tiga aset kanonis;
- referensi halaman ke `akses-disabilitas.jpg`, `posbakum.jpg`, dan foto briefing PTSP tidak tersisa pada area yang diganti;
- markup memuat alt, caption, lazy loading, dan lightbox trigger;
- CSS bersama memuat desktop, mobile, focus-visible, dark mode, dan reduced-motion.

Smoke test Chromium:

- ketiga route desktop 1366×768 menampilkan panel dokumenter pada urutan yang disetujui;
- ketiga route mobile 390×844 tidak overflow dan subjek foto tidak terpotong buruk;
- lightbox dapat dibuka dan ditutup dengan keyboard;
- mode gelap mempertahankan kontras caption dan focus;
- Galeri Fasilitas Publik homepage memakai foto PTSP baru;
- infografik PTSP dan seluruh informasi layanan tetap tersedia.

## Non-sasaran

- Tidak mengubah fakta, persyaratan, alur, jam layanan, nomor kontak, atau copy layanan.
- Tidak menghapus foto lama bila masih direferensikan area lain; penghapusan hanya boleh dilakukan setelah pencarian referensi membuktikan aset benar-benar tidak dipakai.
- Tidak mengubah kartu Lokasi Kami atau foto gedung.
- Tidak melakukan manipulasi generatif pada dokumentasi manusia atau ruang.
