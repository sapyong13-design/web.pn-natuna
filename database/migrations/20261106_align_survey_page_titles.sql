-- Samakan judul dan penjelasan tiga halaman laporan survei dengan nama folder
-- Drive-nya, dan buang keterangan yang sudah tidak benar.
--
-- Sumber data sekarang satu folder induk berisi tiga subfolder:
--   Laporan SKM · Laporan IPAK · Laporan Survei Harian
--
-- Yang diperbaiki:
--
--   1. "Laporan SPAK" muncul di navigasi 13 artikel dan 2 baris menu Joomla.
--      Tidak ada dokumen bernama SPAK; yang dimaksud IPAK (Indeks Persepsi Anti
--      Korupsi). Judul halaman artikel 43 sudah "Laporan IPAK" sejak
--      20260805, jadi navigasi dan menunya yang tertinggal.
--   2. Artikel 42 berjudul "Laporan IKM" sementara folder dan navigasinya
--      menyebut SKM. IKM adalah istilah arsip lama; catatan di halaman tetap
--      menjelaskan padanannya, jadi judulnya aman dimutakhirkan.
--   3. Artikel 85 masih menampilkan "November–Desember 2025 dan periode
--      berjalan 2026 — Belum tersedia", padahal Januari–Juni 2026 sudah
--      dipublikasikan. Keterangan itu sudah tidak benar.
--   4. Catatan ketiga halaman hanya mengulang judul. Diganti kalimat yang
--      menerangkan istilah dan cakupan berkasnya.
--
-- Alias URL (laporan-skm, laporan-spak) sengaja TIDAK diubah: itu alamat yang
-- sudah terindeks mesin pencari dan tertanam di menu.
--
-- Daftar kartu dokumennya sendiri TIDAK ditulis di sini. Itu tugas
-- tools/refresh-surveys.py, yang membaca folder Drive dan dijalankan cron
-- mingguan. Migrasi ini hanya membereskan judul, menu, dan keterangan.

-- 1. Navigasi di dalam artikel: SPAK -> IPAK.
UPDATE #__content
SET introtext = REPLACE(introtext, '>Laporan SPAK</a>', '>Laporan IPAK</a>'),
    modified = UTC_TIMESTAMP()
WHERE introtext LIKE '%>Laporan SPAK</a>%';

-- 1b. Menu Joomla juga menyebut SPAK; samakan supaya tidak ada dua nama.
UPDATE #__menu
SET title = 'Laporan IPAK'
WHERE title = 'Laporan SPAK';

-- 2. Navigasi: SKM / IKM -> SKM.
UPDATE #__content
SET introtext = REPLACE(introtext, '>Laporan SKM / IKM</a>', '>Laporan SKM</a>'),
    modified = UTC_TIMESTAMP()
WHERE introtext LIKE '%>Laporan SKM / IKM</a>%';

-- 3. Judul dan ringkasan artikel 42.
UPDATE #__content
SET title = 'Laporan SKM',
    introtext = REPLACE(REPLACE(introtext,
        '<h2>Laporan IKM</h2>', '<h2>Laporan SKM</h2>'),
        'Informasi resmi laporan IKM ', 'Informasi resmi laporan SKM '),
    modified = UTC_TIMESTAMP()
WHERE id = 42;

-- 4. Hapus keterangan "belum tersedia" yang sudah tidak berlaku. Status baru
--    dihitung ulang oleh refresh-surveys.py dari isi folder Drive.
UPDATE #__content
SET introtext = REPLACE(introtext,
        '<div class="transparency-status is-missing"><strong>November–Desember 2025 dan periode berjalan 2026</strong><span>Belum tersedia pada portal</span></div>',
        ''),
    modified = UTC_TIMESTAMP()
WHERE id = 85
  AND LOCATE('<div class="transparency-status is-missing"><strong>November–Desember 2025 dan periode berjalan 2026</strong>', introtext) > 0;

-- 5. Catatan penjelas per halaman: istilah, cakupan arsip, dan cara berkas
--    digabung. Bukan pengulangan judul, bukan pengulangan paragraf hero.
--
--    Predikat idempoten memakai literal, bukan variabel @note_*. Variabel
--    pengguna punya coercibility sama dengan kolom, sehingga LOCATE(@var, kolom)
--    gagal dengan "Illegal mix of collations" ketika koneksi memakai
--    utf8mb4_general_ci sementara kolomnya utf8mb4_unicode_ci. Literal mengikuti
--    collation kolom, jadi aman di kedua server.

UPDATE #__content
SET introtext = REPLACE(REPLACE(introtext,
        '<p class="transparency-note">SKM adalah Survei Kepuasan Masyarakat; arsip lama menggunakan istilah IKM atau Indeks Kepuasan Masyarakat.</p>', ''),
        '<section class="transparency-archive"',
        CONCAT('<p class="transparency-note">Dokumen 2026 memakai istilah SKM; arsip 2024&ndash;2025 masih berjudul IKM (Indeks Kepuasan Masyarakat) untuk pengukuran yang sama. Laporan terbaru diletakkan paling atas.</p>',
               '<section class="transparency-archive"')),
    modified = UTC_TIMESTAMP()
WHERE id = 42
  AND LOCATE('<p class="transparency-note">Dokumen 2026 memakai istilah SKM', introtext) = 0
  AND LOCATE('<section class="transparency-archive"', introtext) > 0;

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<section class="transparency-archive"',
        CONCAT('<p class="transparency-note">Halaman ini sebelumnya memakai nama SPAK (Survei Persepsi Anti Korupsi) untuk dokumen yang sama. Laporan Triwulan III dan IV 2024 digabung dalam satu berkas. Laporan terbaru diletakkan paling atas.</p>',
               '<section class="transparency-archive"')),
    modified = UTC_TIMESTAMP()
WHERE id = 43
  AND LOCATE('<p class="transparency-note">Halaman ini sebelumnya memakai nama SPAK', introtext) = 0
  AND LOCATE('<section class="transparency-archive"', introtext) > 0;

UPDATE #__content
SET introtext = REPLACE(REPLACE(introtext,
        '<p class="transparency-note">Dokumen “survei harian” dipublikasikan sebagai rekap bulanan.</p>', ''),
        '<section class="transparency-archive"',
        CONCAT('<p class="transparency-note">Rekap disusun per bulan, bukan per hari. Arsip tersedia mulai Januari 2025. Laporan terbaru diletakkan paling atas.</p>',
               '<section class="transparency-archive"')),
    modified = UTC_TIMESTAMP()
WHERE id = 85
  AND LOCATE('<p class="transparency-note">Rekap disusun per bulan, bukan per hari.', introtext) = 0
  AND LOCATE('<section class="transparency-archive"', introtext) > 0;
