-- Koreksi untuk 20260903: status arsip ternyata belum cukup.
--
-- Joomla tetap merender artikel berstatus arsip (state=2) pada URL-nya dengan HTTP 200,
-- jadi salinan duplikat Posbakum di kanal Berita masih terbuka lengkap dan tampak
-- berlaku - hanya hilang dari daftar dan pencarian. Plugin System - Redirect hanya
-- menyala pada 404, sehingga pengalihan yang didaftarkan migrasi sebelumnya tidak
-- pernah terpakai.
--
-- Status diturunkan menjadi tidak terbit (state=0): tamu mendapat 404, plugin redirect
-- menangkapnya, lalu mengantar ke versi kanonis di kanal Pengumuman. Isi artikel tetap
-- utuh di basis data dan dapat diterbitkan kembali kapan saja dari layar admin.
UPDATE #__content SET state=0, modified=UTC_TIMESTAMP()
WHERE alias IN ('legacy-pengumuman-seleksi-posbakum-tahun-2026','legacy-pengumuman-penetapan-dan-pemenang-posbakum-ta-2026')
  AND state <> 0;
