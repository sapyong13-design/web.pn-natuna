# AMPUH 2026 Dossier Institusional — Spesifikasi Refinement

## Tujuan

Merapikan halaman `/ampuh` menjadi direktori arsip premium yang formal, berwibawa, cepat dipindai, dan kuat untuk presentasi publik. Refinement tidak mengubah dataset, URL Drive, struktur 27 GOBI, atau perilaku pencarian inti.

## Arah visual

Konsep: sampul dan isi dossier resmi pengadilan. Bukan dashboard SaaS, bukan kumpulan kartu, dan bukan imitasi kertas kuno.

Palet memakai token PN Natuna:

- maroon sebagai identitas dan aksi utama;
- krem kertas untuk permukaan arsip;
- tinta arang untuk teks;
- emas kusam sebagai aksen maksimum 10%;
- neutral charcoal dan ivory untuk mode gelap.

Tipografi tetap memakai Fraunces untuk judul display dan Plus Jakarta Sans untuk isi. Tidak menambah font atau dependency.

## Hero dossier

Hero menjadi komposisi asimetris dua bidang:

- bidang utama berisi kicker `Direktori Bukti`, h1 `AMPUH 2026 Checklist`, deskripsi maksimal 70 karakter per baris, dan CTA Drive;
- bidang sekunder menampilkan watermark `2026`, label `Pengadilan Negeri Natuna`, dan penanda `Arsip Publik`;
- satu garis register horizontal mengikat kedua bidang;
- tekstur grain sangat halus memakai pseudo-element CSS, tanpa gambar tambahan dan tanpa blur.

Desktop hero maksimal 360 px. Mobile maksimal 300 px. CTA minimal 44 px dan tetap satu-satunya aksi dominan.

## Indeks koleksi

Ringkasan 27 GOBI, 82 checklist, 405 sub-checklist, dan 2.043 dokumen menjadi satu pita `Indeks Koleksi`.

- Desktop: empat nilai dalam satu baris beraturan.
- Mobile: grid 2×2 compact.
- Angka tidak dibuat sebagai kartu metric terpisah.
- Label dan angka tetap dibaca sebagai `<dl>`.

## Toolbar arsip

Pencarian dan navigasi GOBI digabung menjadi satu toolbar:

- label pencarian ringkas;
- input search dengan ikon dekoratif;
- status hasil live;
- tombol `Tutup semua` muncul sebagai aksi sekunder;
- filter GOBI desktop menjadi rail horizontal satu baris dengan scroll-x dan scroll-snap bila ruang kurang;
- mobile memakai `<select>` native berlabel `Pilih GOBI`, menggantikan 27 tombol visual;
- tombol filter desktop tetap tersedia bagi keyboard dan memakai `aria-pressed`.

Toolbar desktop maksimal 190 px. Toolbar mobile maksimal 230 px saat tidak ada pesan error. Ini menggantikan kondisi sekarang sekitar 352 px desktop dan 855 px mobile.

## Daftar GOBI

Daftar tertutup memakai pola dossier row, bukan kartu berulang:

- nomor GOBI besar dalam kolom tetap;
- label `GOBI` kecil;
- metadata checklist/sub-checklist/dokumen di tengah;
- chevron di kanan;
- garis pemisah penuh, background bergantian sangat halus setiap baris kedua;
- tidak memakai side-stripe accent atau shadow pada setiap row.

Target tinggi:

- desktop 76–88 px;
- mobile 72–82 px.

Seluruh 27 GOBI tertutup harus menghasilkan tinggi direktori yang jauh lebih pendek. Target halaman mobile tertutup maksimal 3.900 px, turun dari 5.307 px.

## Checklist dan sub-checklist

Saat GOBI dibuka:

- panel memakai bidang krem/neutral tunggal;
- checklist tampil sebagai baris daftar isi bernomor, tidak sebagai nested card;
- nomor checklist menjadi kolom sempit;
- judul dan metadata memakai kolom fleksibel;
- tombol folder Drive berada sejajar di kanan desktop dan turun ke baris sendiri pada mobile;
- checklist yang sama muncul di dua GOBI tetap terlihat sebagai cabang berbeda tanpa mengubah nomor.

Sub-checklist:

- menggunakan nomor gabungan seperti `6.5` sebagai indeks;
- uraian dibatasi 75ch pada desktop;
- jumlah dokumen dan status link berada pada metadata row;
- tidak mengulang nomor sub-checklist di dalam judul bila sumber title sudah diawali nomor. Renderer harus menampilkan satu nomor saja secara visual.

## Lampiran file

Daftar file menjadi lembar lampiran:

- file disusun satu baris per dokumen;
- ikon tipe file monokrom dan dekoratif;
- nama file bisa membungkus dan disalin;
- folder path provenance, bila tersedia, tampil sebagai teks kecil sekunder;
- warna bukan satu-satunya pembeda tipe file;
- tidak menambah link file individual.

## Motion

- panel reveal memakai opacity dan translateY maksimum 6 px;
- durasi 180–260 ms dengan ease-out-quart;
- tidak menganimasikan height, grid, margin, padding, background, atau layout properties;
- `prefers-reduced-motion: reduce` menonaktifkan seluruh transition/animation AMPUH.

## Dark mode

Mode gelap menyerupai ruang arsip malam:

- background charcoal;
- permukaan dossier sedikit lebih terang;
- judul ivory;
- maroon CTA mempertahankan rasio kontras minimal 4.5:1;
- emas kusam hanya untuk detail tipis;
- rail, separator, metadata, focus ring, dan file icon tetap terbaca.

Kontrak test menghitung rasio h1, body, CTA normal, CTA hover/focus, dan metadata terhadap surface masing-masing.

## Responsive

### Desktop ≥ 1024 px

- lebar editorial maksimal 1180 px;
- hero dua bidang;
- toolbar search + aksi pada satu row;
- filter rail di row kedua;
- dossier rows tiga area: nomor, isi, affordance.

### Tablet 761–1023 px

- hero tetap dua bidang dengan proporsi lebih rapat;
- toolbar boleh membungkus menjadi dua row;
- filter tetap rail horizontal.

### Mobile ≤ 760 px

- hero satu kolom;
- watermark menjadi elemen latar, tidak mengambil kolom sendiri;
- indeks 2×2;
- tombol GOBI desktop disembunyikan, select mobile ditampilkan;
- dossier row tetap satu tombol penuh dengan grid nomor/isi/chevron;
- metadata maksimal dua baris;
- tidak ada horizontal overflow pada 320, 360, 390, atau 430 px.

## Aksesibilitas

- heading hierarchy tetap valid;
- tombol disclosure mempertahankan `aria-expanded` dan `aria-controls`;
- filter desktop dan select mobile tetap sinkron;
- focus ring minimal 2 px dan tidak terpotong;
- target sentuh minimal 44×44 px;
- tekstur dan watermark `aria-hidden`/pseudo-element;
- status hasil tetap live region;
- semua fungsi dapat dioperasikan dengan keyboard.

## Perubahan markup terbatas

Renderer boleh ditata ulang untuk:

- wrapper hero secondary/watermark;
- label `Indeks Koleksi`;
- toolbar desktop/mobile;
- struktur nomor/title/meta GOBI dan checklist;
- select mobile;
- folder path file;
- menghapus duplikasi nomor visual pada judul sub-checklist.

Dataset dan URL tidak berubah.

## Verifikasi

### Contract tests

- ringkasan tetap 27/82/405/2043;
- desktop filter 27 tombol dengan state awal false;
- mobile select memiliki 27 opsi plus opsi semua GOBI;
- search, filter button, dan select menghasilkan state yang sama;
- clear search mempertahankan filter aktif;
- row metadata dan file icons tetap ada;
- dark contrast minimum 4.5:1;
- CSS motion hanya opacity/transform;
- mobile toolbar dan row rules terikat pada breakpoint 760 px.

### Browser QA

- desktop 1440×1000 light/dark;
- tablet 900×900;
- mobile 390×844 light/dark;
- mobile sempit 320×700;
- panjang halaman mobile tertutup maksimal 3.900 px;
- tidak ada overflow horizontal;
- membuka GOBI, checklist, sub-checklist, file list;
- pencarian nama file dan clear reset;
- sinkronisasi rail/select GOBI;
- keyboard Tab/Enter/Space;
- reduced motion;
- tidak ada console error.
