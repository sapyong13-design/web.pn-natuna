# Showcase Pengumuman Baru

**Tanggal:** 16 Juli 2026  
**Status:** Disetujui untuk perencanaan implementasi

## Tujuan

Mengganti blok homepage **Berita Terbaru** dengan showcase **Pengumuman Baru** yang lebih kuat secara visual dan otomatis mengikuti tiga pengumuman resmi terbaru.

## Ruang lingkup

- Mengubah renderer homepage `pn_natuna_render_latest_news()` menjadi renderer khusus pengumuman.
- Mengambil maksimal tiga artikel terbit terbaru dari kategori Pengumuman, ID 13.
- Mengarahkan aksi utama ke `/pengumuman`.
- Mengubah komposisi tiga kartu setara menjadi satu pengumuman utama dan dua pengumuman ringkas.
- Menjaga hero slider Berita dan Pengumuman, portal, serta kanal kategori tetap tidak berubah.
- Tidak mengubah isi artikel atau data Joomla.

## Konten

Header showcase:

- Kicker: **Informasi Resmi**
- Judul: **Pengumuman Baru**
- Deskripsi: **Informasi, pemberitahuan, dan agenda resmi terbaru Pengadilan Negeri Natuna.**
- Aksi: **Semua Pengumuman →** menuju `/pengumuman`

Urutan artikel memakai aturan kanonis Joomla proyek:

1. Pakai `publish_up` jika nilainya lebih baru dari `2000-01-02 00:00:00`.
2. Selain itu, pakai `created`.
3. Urutkan menurun dan batasi tiga artikel.
4. Hanya tampilkan artikel published yang berada dalam rentang `publish_up` dan `publish_down`.

## Komposisi visual

### Desktop, lebih dari 900 px

Showcase memakai grid asimetris sekitar 60:40.

- Kolom kiri: pengumuman terbaru sebagai feature utama.
- Kolom kanan: pengumuman kedua dan ketiga bertumpuk vertikal.
- Feature utama menampilkan gambar lebar, badge **Terbaru**, tanggal lengkap, judul, ringkasan, dan label aksi **Baca Pengumuman**.
- Item ringkas menampilkan thumbnail, tanggal, judul, dan indikator arah. Ringkasan tidak diperlukan agar hierarki tetap jelas.

### Tablet dan mobile, 900 px atau kurang

- Semua item menjadi satu kolom.
- Feature utama tetap pertama dan dominan.
- Dua item berikutnya memakai baris ringkas dengan thumbnail di kiri dan teks di kanan.
- Pada layar sempit, ukuran gambar dan teks tidak menyebabkan overflow horizontal.

## Gambar

Urutan sumber gambar:

1. `image_fulltext` jika tidak kosong.
2. `image_intro` jika tidak kosong.
3. `/images/brand/pengumuman-resmi-pn-natuna.webp` sebagai fallback.

Gambar dekoratif memakai `alt=""` karena judul artikel tersedia pada tautan yang sama. Semua gambar showcase di bawah fold memakai lazy loading dan asynchronous decoding.

## Interaksi dan aksesibilitas

- Seluruh feature dan item ringkas menjadi satu target tautan masing-masing.
- Focus state harus setara dengan hover dan terlihat jelas.
- Hover memakai perubahan warna, elevasi halus, dan pembesaran gambar ringan tanpa mengubah layout.
- Animasi hanya `transform`, warna, border, dan shadow; hormati aturan reduced motion proyek.
- Urutan DOM sama dengan urutan visual agar pembacaan keyboard dan screen reader konsisten.
- Heading showcase tetap `h2`.
- Badge tidak menjadi satu-satunya penanda artikel terbaru; posisi pertama dan tanggal tetap memberi konteks.

## Kondisi data

- Tiga artikel atau lebih: tampilkan satu utama dan dua ringkas.
- Dua artikel: tampilkan satu utama dan satu ringkas tanpa ruang kosong.
- Satu artikel: feature utama memakai lebar penuh.
- Tidak ada artikel: renderer tidak mengeluarkan blok kosong, mengikuti perilaku sekarang.
- Ringkasan kosong: feature tetap valid tanpa placeholder buatan.

## Implementasi

File utama:

- `templates/pn_natuna_2026/hero-slider.php`
- `templates/pn_natuna_2026/css/template.css`

Renderer memakai helper query, URL, tanggal, excerpt, dan gambar yang sudah ada. Tidak membuat query atau lapisan data kedua. Nama class baru berorientasi pengumuman agar aturan kartu berita lama tidak mencampur dua pola visual.

## Verifikasi

Kontrak otomatis harus membuktikan:

- Renderer meminta kategori 13, bukan kategori 12.
- Header dan tautan kanal sesuai teks yang disetujui.
- Artikel pertama memakai markup feature; artikel kedua dan ketiga memakai markup ringkas.
- Fallback gambar pengumuman tetap tersedia.
- Kondisi satu, dua, tiga, dan nol artikel tidak menghasilkan markup rusak.

Smoke test browser:

- Homepage desktop 1366×768 menampilkan feature utama dan dua item ringkas.
- Homepage mobile 390×844 menampilkan urutan satu kolom tanpa overflow.
- Setiap kartu membuka artikel pengumuman yang sesuai.
- Tombol **Semua Pengumuman** membuka `/pengumuman`.
- Focus keyboard terlihat dan mode gelap tetap terbaca.

## Batas non-sasaran

- Tidak mengubah hero slider.
- Tidak menambah carousel, autoplay, modal, filter, atau pemilihan manual.
- Tidak mengubah struktur kanal `/berita`, `/pengumuman`, atau portal `/berita-dan-pengumuman`.
- Tidak menambah migrasi DB karena perubahan hanya renderer dan CSS.
