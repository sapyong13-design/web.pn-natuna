-- Mendaftarkan plugin konten `pnnatunaimagevariants` yang membuat varian foto
-- responsif begitu artikel disimpan. Joomla tidak pernah memperkecil berkas saat
-- diunggah, dan templat artikel hanya memasang `srcset` bila varian itu ada, jadi
-- tanpa plugin ini setiap berita baru mengirim foto ukuran penuh ke ponsel sampai
-- ada yang ingat menjalankan `php tools/make-image-variants.php`.
--
-- Dua pernyataan di bawah aman diputar ulang: baris hanya disisipkan bila belum ada,
-- lalu statusnya dinormalkan menjadi aktif.

INSERT INTO #__extensions (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state)
SELECT 0, 'plg_content_pnnatunaimagevariants', 'plugin', 'pnnatunaimagevariants', 'content', 0, 1, 1, 0, 0, '', '{}', '', NULL, NULL, 0, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='content' AND element='pnnatunaimagevariants') AS existing
);

UPDATE #__extensions SET enabled=1, state=0 WHERE type='plugin' AND folder='content' AND element='pnnatunaimagevariants';
