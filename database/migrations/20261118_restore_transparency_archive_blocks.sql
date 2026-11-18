-- Normalisasi blok arsip tiga halaman Transparansi yang belum pernah disinkronkan.
--
-- `tools/refresh-surveys.py` hanya mengganti isi <section class="transparency-archive">
-- yang SUDAH ada; ia tidak menambahkannya. Artikel 88 ("Peraturan dan Kebijakan") di
-- produksi berakhir dengan status "Sedang dikurasi" tanpa blok arsip sama sekali,
-- sehingga setiap sinkronisasi harian melewatinya dengan
-- "[WARN] artikel 88 tidak punya blok arsip" dan sebelas kartu Drive-nya tidak pernah
-- tampil. Artikel 89 dan 90 di produksi memakai markup polos tanpa pembungkus
-- `.transparency-page`, berbeda dari lokal.
--
-- Migrasi ini menormalkan ketiganya ke bentuk yang dipakai halaman Transparansi lain:
-- pembungkus, header, navigasi lokal, blok arsip kosong, dan status. Blok arsip
-- sengaja dibiarkan kosong - sinkronisasi harian yang mengisinya dari Drive.
--
-- Idempoten: setiap UPDATE dijaga keberadaan penanda, jadi menjalankan ulang tidak
-- menimpa kartu yang sudah terisi.

UPDATE `#__content`
SET introtext = CONCAT(
        '<div class="transparency-page">',
        '<a class="transparency-back" href="/transparansi">&#8592; Kembali ke Transparansi</a>',
        '<header class="transparency-header">',
        '<span>Informasi publik</span>',
        '<h2>Peraturan dan Kebijakan</h2>',
        '<p>Informasi resmi peraturan dan kebijakan Pengadilan Negeri Natuna untuk mendukung keterbukaan dan akuntabilitas publik.</p>',
        '<div class="transparency-chips"><span>Arsip tersedia</span><span>Tujuan eksternal diberi penanda</span></div>',
        '</header>',
        '<nav class="transparency-local-nav" aria-label="Halaman transparansi terkait"><div><strong>Kinerja</strong><a href="/transparansi/ringkasan-lkjip">Ringkasan LKjIP</a><a href="/transparansi/laporan-tahunan">Laporan Tahunan</a><a href="/transparansi/sakip">SAKIP</a></div><div><strong>Keuangan &amp; aset</strong><a href="/transparansi/realisasi-anggaran">Realisasi Anggaran</a><a href="/transparansi/laporan-keuangan">Laporan Keuangan</a><a href="/transparansi/lhkpn">LHKPN / LHKASN</a></div><div><strong>Layanan publik</strong><a href="/transparansi/laporan-skm">SKM</a><a href="/transparansi/laporan-spak">IPAK</a><a href="/transparansi/laporan-survei-harian">Survei Harian</a><a href="/transparansi/e-brosur">E-Brosur</a><a href="/transparansi/peraturan-dan-kebijakan">Peraturan dan Kebijakan</a><a href="/transparansi/lelang-barang-jasa">Lelang Barang dan Jasa</a><a href="/transparansi/laporan-pelayanan-informasi-publik">Layanan Informasi Publik</a></div></nav>',
        '<section class="transparency-archive" aria-label="Daftar dokumen"></section>',
        '</div>'
    ),
    modified = UTC_TIMESTAMP()
WHERE id = 88
  AND title = 'Peraturan dan Kebijakan'
  AND introtext NOT LIKE '%transparency-archive%';

UPDATE `#__content`
SET introtext = CONCAT(
        '<div class="transparency-page">',
        '<a class="transparency-back" href="/transparansi">&#8592; Kembali ke Transparansi</a>',
        '<header class="transparency-header">',
        '<span>Informasi publik</span>',
        '<h2>Laporan Pelayanan Informasi Publik</h2>',
        '<p>Laporan pelayanan informasi publik memuat ringkasan layanan permohonan informasi, pengelolaan informasi publik, dan tindak lanjut layanan informasi pada Pengadilan Negeri Natuna.</p>',
        '<div class="transparency-chips"><span>Arsip tersedia</span><span>Tujuan eksternal diberi penanda</span></div>',
        '</header>',
        '<nav class="transparency-local-nav" aria-label="Halaman transparansi terkait"><div><strong>Kinerja</strong><a href="/transparansi/ringkasan-lkjip">Ringkasan LKjIP</a><a href="/transparansi/laporan-tahunan">Laporan Tahunan</a><a href="/transparansi/sakip">SAKIP</a></div><div><strong>Keuangan &amp; aset</strong><a href="/transparansi/realisasi-anggaran">Realisasi Anggaran</a><a href="/transparansi/laporan-keuangan">Laporan Keuangan</a><a href="/transparansi/lhkpn">LHKPN / LHKASN</a></div><div><strong>Layanan publik</strong><a href="/transparansi/laporan-skm">SKM</a><a href="/transparansi/laporan-spak">IPAK</a><a href="/transparansi/laporan-survei-harian">Survei Harian</a><a href="/transparansi/e-brosur">E-Brosur</a><a href="/transparansi/peraturan-dan-kebijakan">Peraturan dan Kebijakan</a><a href="/transparansi/lelang-barang-jasa">Lelang Barang dan Jasa</a><a href="/transparansi/laporan-pelayanan-informasi-publik">Layanan Informasi Publik</a></div></nav>',
        '<section class="transparency-archive" aria-label="Daftar dokumen"></section>',
        '</div>'
    ),
    modified = UTC_TIMESTAMP()
WHERE id = 89
  AND title = 'Laporan Pelayanan Informasi Publik'
  AND introtext NOT LIKE '%transparency-archive%';

UPDATE `#__content`
SET introtext = CONCAT(
        '<div class="transparency-page">',
        '<a class="transparency-back" href="/transparansi">&#8592; Kembali ke Transparansi</a>',
        '<header class="transparency-header">',
        '<span>Informasi publik</span>',
        '<h2>Lelang Barang dan Jasa</h2>',
        '<p>Informasi lelang barang dan jasa Pengadilan Negeri Natuna disediakan sebagai bagian dari keterbukaan informasi pengadaan dan pengelolaan barang milik negara.</p>',
        '<div class="transparency-chips"><span>Arsip tersedia</span><span>Tujuan eksternal diberi penanda</span></div>',
        '</header>',
        '<nav class="transparency-local-nav" aria-label="Halaman transparansi terkait"><div><strong>Kinerja</strong><a href="/transparansi/ringkasan-lkjip">Ringkasan LKjIP</a><a href="/transparansi/laporan-tahunan">Laporan Tahunan</a><a href="/transparansi/sakip">SAKIP</a></div><div><strong>Keuangan &amp; aset</strong><a href="/transparansi/realisasi-anggaran">Realisasi Anggaran</a><a href="/transparansi/laporan-keuangan">Laporan Keuangan</a><a href="/transparansi/lhkpn">LHKPN / LHKASN</a></div><div><strong>Layanan publik</strong><a href="/transparansi/laporan-skm">SKM</a><a href="/transparansi/laporan-spak">IPAK</a><a href="/transparansi/laporan-survei-harian">Survei Harian</a><a href="/transparansi/e-brosur">E-Brosur</a><a href="/transparansi/peraturan-dan-kebijakan">Peraturan dan Kebijakan</a><a href="/transparansi/lelang-barang-jasa">Lelang Barang dan Jasa</a><a href="/transparansi/laporan-pelayanan-informasi-publik">Layanan Informasi Publik</a></div></nav>',
        '<section class="transparency-archive" aria-label="Daftar dokumen"></section>',
        '</div>'
    ),
    modified = UTC_TIMESTAMP()
WHERE id = 90
  AND title = 'Lelang Barang dan Jasa'
  AND introtext NOT LIKE '%transparency-archive%';
