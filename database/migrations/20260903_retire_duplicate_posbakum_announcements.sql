-- Dua pengumuman Posbakum tayang ganda di dua kanal sekaligus.
--
-- Versi kanonisnya berdiri di kanal Pengumuman; salinannya di kanal Berita berasal dari
-- impor lama, isinya lebih pendek (123 vs 429 karakter pada seleksi Posbakum), dan satu
-- di antaranya berjudul KAPITAL SEMUA. Akibatnya warga menemukan pengumuman yang sama
-- dua kali di daftar dan dua kali lagi di hasil pencarian, dengan isi yang berbeda
-- panjang - dan tidak ada petunjuk mana yang berlaku.
--
-- Salinan Berita diarsipkan, bukan dihapus: isinya tetap tersimpan dan bisa dikembalikan
-- kapan saja. Tautan lamanya dialihkan permanen ke versi kanonis supaya pranala yang
-- beredar di WhatsApp tidak berujung 404.
SET @seleksi_kanonis := (SELECT id FROM #__content WHERE alias='pengumuman-seleksi-posbakum-tahun-2026' LIMIT 1);
SET @pemenang_kanonis := (SELECT id FROM #__content WHERE alias='pengumuman-penetapan-dan-pemenang-posbakum-ta-2026' LIMIT 1);
CREATE TEMPORARY TABLE posbakum_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=2));
INSERT INTO posbakum_dependency_check VALUES ((@seleksi_kanonis IS NOT NULL)+(@pemenang_kanonis IS NOT NULL));
DROP TEMPORARY TABLE posbakum_dependency_check;

-- state 2 = archived. Idempoten: menjalankan ulang tidak mengubah apa pun.
UPDATE #__content SET state=2, modified=UTC_TIMESTAMP()
WHERE alias IN ('legacy-pengumuman-seleksi-posbakum-tahun-2026','legacy-pengumuman-penetapan-dan-pemenang-posbakum-ta-2026')
  AND state <> 2;

INSERT INTO #__redirect_links (old_url, new_url, referer, comment, hits, published, created_date, modified_date)
SELECT '/berita/legacy-pengumuman-seleksi-posbakum-tahun-2026','/berita-dan-pengumuman/pengumuman/pengumuman-seleksi-posbakum-tahun-2026','','Duplikat kanal Berita diarsipkan; versi kanonis ada di kanal Pengumuman.',0,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM #__redirect_links WHERE old_url='/berita/legacy-pengumuman-seleksi-posbakum-tahun-2026' LIMIT 1) existing);

INSERT INTO #__redirect_links (old_url, new_url, referer, comment, hits, published, created_date, modified_date)
SELECT '/berita/legacy-pengumuman-penetapan-dan-pemenang-posbakum-ta-2026','/berita-dan-pengumuman/pengumuman/pengumuman-penetapan-dan-pemenang-posbakum-ta-2026','','Duplikat kanal Berita diarsipkan; versi kanonis ada di kanal Pengumuman.',0,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM #__redirect_links WHERE old_url='/berita/legacy-pengumuman-penetapan-dan-pemenang-posbakum-ta-2026' LIMIT 1) existing);

-- Plugin System - Redirect wajib aktif, kalau tidak tabel di atas tidak pernah dibaca.
UPDATE #__extensions SET enabled=1 WHERE element='redirect' AND type='plugin' AND folder='system';
