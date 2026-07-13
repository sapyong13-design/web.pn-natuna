# Direktori AMPUH 2026 — Spesifikasi Desain

> **Status:** desain awal. Keputusan operasional final: instalasi pertama berlangsung privat di `new.pn-natuna.go.id`, source update melalui private checkout GitHub dan `git pull --ff-only`, lalu cutover ke `pn-natuna.go.id`. Lihat `CPANEL-STAGING-CUTOVER-RUNBOOK.md` dan `HANDOFF.md`.

## Tujuan

Membangun route `/ampuh` sebagai direktori bukti dokumen AMPUH 2026 Pengadilan Negeri Natuna. Route diuji pertama pada staging `new.pn-natuna.go.id`, lalu ikut menggantikan website utama saat cutover. Halaman memakai identitas visual dan pola interaksi situs utama PN Natuna, mudah dipresentasikan, serta memudahkan pengunjung menemukan lokasi dokumen berdasarkan GOBI, checklist besar, dan sub-checklist.

Halaman bukan dashboard progres. Seluruh dokumen dianggap lengkap. Tidak ada persentase, status upload, status print, catatan internal, atau status tindak lanjut.

## Audiens dan akses

- Halaman dapat diakses publik tanpa login.
- Semua GOBI, checklist, sub-checklist, nama file, dan tautan Google Drive ditampilkan publik.
- Google Drive hanya memberikan akses lihat.
- Semua tautan Drive dibuka di tab baru dan diberi penanda tautan eksternal.

## Integrasi Joomla

Direktori menjadi bagian dari instalasi Joomla PN Natuna, bukan aplikasi terpisah. Staging `new.pn-natuna.go.id` memakai document root dan database terpisah sampai cutover ke domain utama.

Halaman menggunakan:

- header dan navigasi situs utama;
- footer situs utama;
- identitas warna dan tipografi PN Natuna;
- mode gelap yang sudah tersedia;
- pola fokus, reduced motion, dan responsif yang sudah tersedia;
- renderer khusus agar katalog tidak ditulis sebagai HTML panjang di artikel Joomla.

## Sumber data

Data awal berasal dari:

`C:\Users\faris\Downloads\ampuh-checklist-2026-merged (1).xlsx`

Workbook saat ini mencatat 405 sub-checklist dan 2.008 dokumen. Dataset final harus menghitung ulang ringkasan dari hasil impor, bukan mengandalkan angka yang diketik manual.

Nama file awal memakai data workbook. Pada tahap lanjutan, nama file dan tautan folder diverifikasi ulang satu per satu dari Google Drive view-only yang diberikan pemilik situs.

## Model data

Data katalog disimpan terpisah dari markup renderer. Setiap tingkat memiliki identitas stabil agar pembaruan nama file atau URL tidak mengubah struktur tampilan.

### GOBI

- nomor GOBI;
- nama kelompok bila tersedia;
- daftar checklist besar;
- jumlah checklist besar, sub-checklist, dan dokumen yang dihitung dari anaknya.

### Checklist besar

Terdapat tepat 82 checklist besar, bernomor 1 sampai 82.

Setiap checklist besar memuat:

- nomor checklist besar;
- judul checklist;
- GOBI induk;
- daftar sub-checklist;
- total sub-checklist;
- total dokumen yang dihitung dari seluruh sub-checklist;
- URL folder Google Drive checklist besar.

### Sub-checklist

Nomor sub-checklist dibentuk dari nomor checklist besar dan nomor sub, misalnya `1.1`, `1.2`, dan `1.3`.

Setiap sub-checklist memuat:

- nomor sub-checklist;
- uraian kriteria;
- jumlah dokumen;
- daftar nama file;
- URL folder Google Drive sub-checklist.

### Nama file

Setiap item file memuat:

- nama file lengkap;
- jenis file yang diturunkan dari ekstensi untuk pemilihan ikon.

Nama file tidak memiliki tautan individual. Tautan hanya tersedia pada folder checklist besar dan folder sub-checklist.

### Tautan folder utama

URL folder utama AMPUH 2026 disimpan sebagai konfigurasi terpisah. URL tersebut digunakan tombol utama pada hero.

## Hierarki informasi

```text
GOBI
└── Checklist besar 1–82
    ├── Tombol folder Google Drive checklist besar
    └── Sub-checklist, misalnya 1.1, 1.2, 1.3
        ├── Tombol folder Google Drive sub-checklist
        └── Daftar nama file
```

Setiap checklist besar memiliki tombol folder Google Drive. Setiap sub-checklist juga memiliki tombol folder Google Drive.

## Tata letak

### Hero

Hero memakai gaya editorial PN Natuna dan memuat:

- kicker AMPUH 2026;
- judul `Direktori AMPUH 2026`;
- deskripsi singkat fungsi direktori;
- tombol utama `Buka Folder Utama AMPUH 2026`;
- ringkasan jumlah GOBI, 82 checklist besar, jumlah sub-checklist, dan jumlah dokumen.

Ringkasan bersifat inventaris faktual, bukan indikator progres.

### Pengantar ringkas

Setelah hero, tampil penjelasan singkat mengenai fungsi AMPUH 2026 dan cara memakai direktori. Teks tidak memuat uraian panjang tentang dasar hukum, tim, atau alur penilaian.

### Alat navigasi

Sebelum daftar direktori tersedia:

- pencarian global;
- filter cepat GOBI;
- tombol `Tutup Semua` yang relevan setelah bagian dibuka;
- jumlah hasil saat pencarian aktif.

### Daftar GOBI

Semua GOBI tertutup saat halaman pertama dibuka. Baris GOBI menampilkan:

- nomor GOBI;
- nama kelompok bila tersedia;
- jumlah checklist besar;
- jumlah sub-checklist;
- jumlah dokumen.

Membuka GOBI menampilkan checklist besar di dalamnya.

### Checklist besar

Baris checklist besar menampilkan:

- nomor 1 sampai 82;
- judul checklist;
- jumlah sub-checklist;
- total dokumen;
- tombol `Buka Folder Checklist`;
- kontrol untuk membuka daftar sub-checklist.

### Sub-checklist

Baris sub-checklist menampilkan:

- nomor lengkap seperti `1.1`;
- uraian kriteria;
- jumlah dokumen;
- tombol `Buka Folder Sub-checklist`;
- kontrol untuk membuka daftar nama file.

### Daftar file

Daftar file menampilkan nama lengkap tanpa dipotong permanen. Setiap file memakai ikon berdasarkan ekstensi, termasuk PDF, spreadsheet, Word, gambar, dan tipe generik. Nama file dapat dipilih dan disalin.

## Perilaku accordion

- Semua GOBI, checklist besar, sub-checklist, dan daftar file tertutup saat halaman pertama dibuka.
- Pengguna dapat membuka lebih dari satu bagian pada tingkat yang sama.
- Membuka bagian tidak menutup bagian lain secara otomatis.
- Tombol `Tutup Semua` mengembalikan seluruh hierarki ke kondisi tertutup.
- State tidak disimpan di browser setelah halaman dimuat ulang.

## Pencarian global

Pencarian mencakup:

- nomor GOBI;
- nomor checklist besar;
- nomor sub-checklist;
- judul checklist besar;
- uraian sub-checklist;
- nama file.

Saat pencarian aktif:

- pencocokan tidak peka huruf besar-kecil;
- bagian yang tidak memiliki kecocokan disembunyikan;
- jalur GOBI, checklist besar, dan sub-checklist menuju hasil dibuka otomatis;
- jumlah hasil ditampilkan;
- pesan kosong ditampilkan bila tidak ada kecocokan.

Saat pencarian dikosongkan:

- semua item tampil kembali;
- seluruh accordion kembali tertutup sesuai kondisi awal.

Filter GOBI dan pencarian dapat digunakan bersama. Hasil harus memenuhi keduanya.

## Responsif

### Desktop

- Konten memakai lebar editorial yang konsisten dengan situs utama.
- Hierarki dibedakan melalui skala tipografi, nomor, latar lembut, dan indentasi terukur.
- Tombol Drive tetap mudah ditemukan tanpa mengalahkan judul checklist.

### Mobile

- Seluruh struktur menjadi satu kolom.
- Metadata membungkus tanpa menyebabkan scroll horizontal.
- Tombol Drive memiliki target sentuh minimal 44 × 44 piksel.
- Nama file panjang membungkus dan tetap dapat dibaca.
- Header, drawer, dan mode gelap mengikuti perilaku situs utama.

## Aksesibilitas

- Accordion memakai tombol semantik dengan `aria-expanded` dan hubungan panel yang jelas.
- Seluruh fungsi tersedia dengan keyboard.
- Fokus terlihat jelas pada tema terang dan gelap.
- Perubahan jumlah hasil dan kondisi hasil kosong diumumkan secara wajar kepada pembaca layar.
- Ikon file bersifat dekoratif bila nama file sudah menjelaskan konten.
- Animasi hanya memakai opacity dan transform, singkat, serta dinonaktifkan saat reduced motion aktif.
- Warna bukan satu-satunya pembeda tingkat hierarki.

## Keadaan data tidak lengkap

Kebijakan final URL Google Drive:

- tombol yang belum memiliki URL tidak menjadi tautan palsu;
- checklist besar tanpa URL dapat memakai label netral bila diperlukan konteks;
- sub-checklist tanpa URL tidak menampilkan placeholder atau ruang action kosong;
- hanya URL HTTPS `drive.google.com` valid yang menjadi tautan;
- tidak menggunakan `#`, URL contoh, atau fallback ke folder yang salah;
- sub-checklist 78.3 adalah satu-satunya sub-checklist dengan URL viewer sendiri pada dataset saat ini.

Baris workbook yang tidak memiliki judul checklist besar harus tetap terikat pada checklist besar terakhir yang valid sesuai struktur merged-cell Excel. Nomor GOBI juga diwariskan dari baris terakhir yang valid sampai nilai GOBI berikutnya muncul.

## Pengolahan workbook

Importer harus:

1. membaca sheet checklist utama dan sheet detail file;
2. menangani sel gabungan dan nilai kosong yang berarti meneruskan induk sebelumnya;
3. membentuk tepat 82 checklist besar;
4. membentuk nomor sub-checklist dari nomor checklist besar dan nomor sub;
5. mempertahankan nama file dan struktur folder yang tersedia;
6. menggabungkan detail file yang diringkas pada sheet utama;
7. menghitung ulang jumlah GOBI, checklist besar, sub-checklist, dan dokumen;
8. memvalidasi bahwa jumlah file per sub-checklist konsisten dengan daftar nama file, lalu melaporkan perbedaan tanpa menghapus data.

Importer menghasilkan dataset deterministik sehingga workbook yang sama selalu menghasilkan keluaran yang sama.

## Keamanan dan privasi

- Dataset dan nama file dianggap informasi publik berdasarkan keputusan pemilik situs.
- Tidak ada kredensial, token, cookie, atau konfigurasi privat Google Drive dalam HTML atau dataset.
- URL view-only disimpan sebagai URL publik biasa.
- Tautan eksternal menggunakan `rel="noopener noreferrer"`.
- Renderer melakukan escaping pada seluruh teks workbook sebelum ditampilkan.
- Importer tidak mengeksekusi formula, macro, atau konten aktif dari workbook.

## Verifikasi

### Kontrak data

- Dataset berisi tepat 82 checklist besar.
- Nomor checklist besar unik dan mencakup 1 sampai 82.
- Setiap sub-checklist memiliki nomor gabungan yang benar.
- Jumlah ringkasan sama dengan agregasi data anak.
- Nama file detail tidak hilang pada entri yang diringkas di sheet utama.

### Kontrak renderer

- Hero menampilkan ringkasan hasil agregasi.
- Tautan folder utama, checklist besar, dan sub-checklist muncul hanya saat URL tersedia.
- Semua accordion tertutup saat awal.
- Pencarian nama file membuka seluruh jalur induknya.
- Menghapus pencarian menutup seluruh accordion.
- Filter GOBI dan pencarian beririsan dengan benar.
- Hasil kosong mempunyai pesan yang jelas.

### QA browser

Verifikasi pada:

- desktop dengan viewport minimal 1280 piksel;
- mobile nyata melalui CDP pada lebar 390 piksel;
- mode terang dan gelap;
- navigasi keyboard;
- reduced motion;
- contoh nama file sangat panjang;
- contoh sub-checklist tanpa URL dan tanpa file.

## Batas tahap pertama

Tahap pertama mencakup:

- importer workbook;
- dataset awal dari seluruh data Excel;
- halaman Joomla lengkap;
- ringkasan inventaris;
- pencarian dan filter GOBI;
- accordion empat tingkat;
- placeholder nonaktif untuk URL yang belum diberikan;
- pengujian data, renderer, dan QA responsif.

Pemeliharaan berikutnya hanya menambah URL viewer publik yang benar-benar tersedia pada dataset/override, tanpa meminjam URL parent dan tanpa mengubah arsitektur halaman. Setiap perubahan data wajib diikuti focused tests.
