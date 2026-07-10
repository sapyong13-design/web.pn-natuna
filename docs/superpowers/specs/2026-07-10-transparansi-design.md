# Desain Portal Transparansi

## Tujuan

Mengubah halaman `/transparansi` dari daftar tautan datar menjadi portal informasi yang mudah dipindai, informatif, menarik, dan konsisten dengan halaman layanan Pengadilan Negeri Natuna. Seluruh 13 tautan serta tujuan URL yang ada dipertahankan.

## Arah Visual

Halaman memakai identitas PN Natuna: marun dominan, aksen emas, bidang terang, Fraunces untuk judul, dan Plus Jakarta Sans untuk isi. Hero marun–emas bertajuk “Transparansi & Akuntabilitas” memakai ilustrasi geometris dokumen dan perisai. Tampilan terasa resmi, modern, dan terpercaya tanpa menyerupai dashboard korporat generik.

## Struktur Halaman

1. Hero berisi judul, ringkasan manfaat, dan ilustrasi dekoratif yang tidak mengganggu aksesibilitas.
2. Navigasi pil menuju empat kelompok konten pada halaman.
3. Empat kelompok kartu:
   - Kinerja & Perencanaan: Laporan Tahunan, Ringkasan LKjIP, SAKIP.
   - Keuangan & Pengadaan: Laporan Realisasi Anggaran DIPA 01 dan 03, Laporan Keuangan PN, Lelang Barang dan Jasa.
   - Integritas & Kualitas Layanan: LHKPN, Laporan SKM, Laporan SPAK, Laporan Survei Harian.
   - Keterbukaan Informasi: Laporan Pelayanan Informasi Publik, E-Brosur, Peraturan dan Kebijakan.
4. CTA penutup mengarahkan pengguna ke PPID atau kontak resmi bila dokumen yang dicari belum ditemukan.

## Komponen dan Perilaku

Setiap kelompok memiliki label, judul, dan deskripsi singkat. Setiap kartu menjadi target klik utuh dan memuat ikon SVG, judul, penjelasan satu baris mengenai isi halaman, serta indikator “Lihat informasi”. Hover hanya memberi pengangkatan dan perubahan aksen ringan. Fokus keyboard harus jelas. Navigasi pil menggunakan tautan jangkar dan mendukung pengguliran halus hanya bila pengguna tidak memilih reduced motion.

Grid memakai tiga kolom pada layar lebar, dua kolom pada tablet, dan satu kolom pada ponsel. Tidak boleh ada overflow horizontal. Dark mode memakai permukaan dan kontras yang mengikuti token template yang sudah ada.

## Data dan Integrasi Joomla

Konten artikel Transparansi diperbarui melalui SQL tersendiri agar perubahan dapat diterapkan ulang dan dibawa ke instalasi lain. CSS baru ditempatkan dalam blok terisolasi di `templates/pn_natuna_2026/css/template.css`, memakai prefiks kelas khusus Transparansi. Tidak ada perubahan struktur menu Joomla, alias, atau tujuan URL 13 tautan.

## Penanganan Kesalahan

Tautan yang belum memiliki isi tetap mempertahankan URL lama; halaman induk tidak menyembunyikannya. CTA PPID/kontak memberi jalur bantuan alternatif. Ikon bersifat dekoratif dan disembunyikan dari pembaca layar. Struktur heading tetap berurutan.

## Verifikasi

- `/transparansi` memuat hero, navigasi, empat kelompok, 13 kartu, dan CTA.
- Semua 13 tautan mempertahankan URL sebelumnya.
- Desktop 1440 px, mobile 390 px, light mode, dan dark mode tidak mengalami overflow atau tumpang tindih.
- Fokus keyboard terlihat pada pil, kartu, dan CTA.
- Heading serta nama tautan terbaca benar lewat accessibility tree.
- Tidak ada error browser atau PHP saat halaman dimuat.
