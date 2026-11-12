-- Sediakan blok arsip untuk tiga halaman Transparansi yang belum punya.
--
-- `tools/refresh-surveys.py` hanya mengganti isi `<section class="transparency-archive">`.
-- Artikel 88 (Peraturan dan Kebijakan) memakai placeholder "Koleksi sedang disiapkan"
-- tanpa blok arsip, sedangkan 89 (Laporan Pelayanan Informasi Publik) dan 90 (Lelang
-- Barang dan Jasa) hanya berisi paragraf pengantar. Tanpa blok ini, sinkronisasi
-- melaporkan "[WARN] artikel tidak punya blok arsip" dan kartunya tidak pernah ditulis.
--
-- Idempoten lewat predikat `NOT LIKE`, bukan lewat REPLACE yang menyisipkan teks ke
-- dalam teks yang sama (bentuk itu berlipat setiap replay).

-- 88: blok arsip diletakkan sebelum keterangan status, di dalam pembungkus halaman.
-- INSERT() menyisipkan pada posisi yang dihitung, jadi hasilnya sama saat diulang
-- selama predikat penjaga masih memenuhi.
UPDATE #__content
SET introtext = INSERT(
        introtext,
        LOCATE('<div class="transparency-status">', introtext),
        CHAR_LENGTH('<div class="transparency-status">'),
        CONCAT(
            '<section class="transparency-archive" aria-label="Daftar dokumen"></section>',
            '<div class="transparency-status">'
        )
    ),
    modified = UTC_TIMESTAMP()
WHERE id = 88
  AND introtext LIKE '%<div class="transparency-status">%'
  AND introtext NOT LIKE '%transparency-archive%';

-- 89 dan 90: kontennya paragraf biasa, jadi blok arsip ditambahkan di akhir.
UPDATE #__content
SET introtext = CONCAT(introtext, '<section class="transparency-archive" aria-label="Daftar dokumen"></section>'),
    modified = UTC_TIMESTAMP()
WHERE id IN (89, 90)
  AND introtext NOT LIKE '%transparency-archive%';

-- Keterangan "belum tersedia" pada 89 dan 90 tetap dipertahankan: blok arsip yang
-- kosong saja tidak memberi tahu pembaca bahwa dokumennya memang belum ada.
UPDATE #__content
SET introtext = CONCAT(
        introtext,
        '<div class="transparency-status is-missing"><strong>Belum tersedia</strong>'
        '<span>Dokumen akan dipublikasikan setelah arsip resmi diverifikasi</span></div>'
    ),
    modified = UTC_TIMESTAMP()
WHERE id IN (89, 90)
  AND introtext NOT LIKE '%transparency-status%';
