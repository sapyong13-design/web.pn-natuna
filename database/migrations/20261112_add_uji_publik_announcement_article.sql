-- Pengumuman "Uji Publik Calon Hakim" di bagian Pengumuman & Video Terbaru.
--
-- Surat Badan Pengawasan Mahkamah Agung Nomor 9436/BP/KP1.1.2/XI/2026 tanggal
-- 11 November 2026 meminta informasi ini disebarluaskan di situs pengadilan, dengan
-- banner yang WAJIB dapat diklik menuju https://siwas.mahkamahagung.go.id/Uji_publik.
--
-- Bentuknya artikel kategori Pengumuman (catid 13), bukan modul, supaya muncul di
-- kartu "Pengumuman terbaru" pada bagian Pengumuman & Video Terbaru — tempat pembaca
-- memang mencari pengumuman resmi. Tanggal terbit dibuat paling baru agar kartu itu
-- yang tampil sebagai sorotan.
--
-- Banner di dalam artikel adalah tautan (sesuai butir 2 surat). Kartu sorotan di
-- beranda memakai `metadata.external_url` sebagai tujuan klik, jadi satu klik dari
-- beranda langsung menuju laman Uji Publik tanpa melewati halaman perantara.
--
-- `images.*` sengaja dikosongkan. Templat artikel merender gambar intro sebagai
-- bingkai 16:9, jadi banner portrait akan muncul dua kali — sekali gepeng di atas
-- (gambar intro) dan sekali utuh di badan artikel. Gambar kartu beranda diambil dari
-- `metadata.card_image`.
--
-- Tanpa tanda kutip tunggal di dalam HTML agar tidak perlu escaping; idempoten lewat
-- alias, sehingga menjalankan ulang memperbarui baris yang sama.

SET @uji_id := (SELECT MIN(id) FROM #__content WHERE alias = 'pengumuman-uji-publik-calon-hakim-2026');

INSERT INTO #__content (
    asset_id, title, alias, introtext, `fulltext`, state, catid, created, created_by,
    created_by_alias, modified, modified_by, images, urls, attribs, version, ordering,
    metakey, metadesc, access, hits, metadata, featured, language, note, publish_up
)
SELECT 0,
    'Pengumuman Uji Publik Calon Hakim Rekrutmen Internal Mahkamah Agung RI',
    'pengumuman-uji-publik-calon-hakim-2026',
    '<p><a href="https://siwas.mahkamahagung.go.id/Uji_publik" target="_blank" rel="noopener noreferrer"><img src="/images/pengumuman/uji-publik-cakim-2026-768.webp" srcset="/images/pengumuman/uji-publik-cakim-2026-480.webp 480w, /images/pengumuman/uji-publik-cakim-2026-768.webp 768w, /images/pengumuman/uji-publik-cakim-2026-1200.webp 1200w, /images/pengumuman/uji-publik-cakim-2026.webp 1414w" sizes="(max-width: 760px) 92vw, 620px" alt="Uji Publik Calon Hakim Rekrutmen Internal Mahkamah Agung RI. Suara Anda, Masa Depan Peradilan. Klik untuk melakukan penilaian." width="768" height="1086" loading="eager" decoding="async"></a></p><p>Mahkamah Agung Republik Indonesia mengundang seluruh lapisan masyarakat untuk memberikan masukan, rekam jejak, dan menilai integritas para Calon Hakim dari jalur rekrutmen internal.</p><p>Mengenal mereka adalah hak Anda. Memastikan mereka berintegritas adalah tanggung jawab kita bersama.</p><p>Banner di atas dapat diklik dan langsung menuju laman resmi Uji Publik. Informasi ini disebarluaskan menindaklanjuti Surat Badan Pengawasan Mahkamah Agung Nomor 9436/BP/KP1.1.2/XI/2026 tanggal 11 November 2026.</p><p><a class="uji-publik-cta" href="https://siwas.mahkamahagung.go.id/Uji_publik" target="_blank" rel="noopener noreferrer">Berikan penilaian di laman Uji Publik &rarr;</a></p>',
    '', 1, 13, '2026-11-11 09:00:00', 0, '', UTC_TIMESTAMP(), 0,
    '{"image_intro":"","image_fulltext":"","float_intro":"","float_fulltext":"","image_intro_alt":"","image_fulltext_alt":""}',
    '{"urla":"https://siwas.mahkamahagung.go.id/Uji_publik","urlatext":"Buka laman Uji Publik","targeta":"_blank","urlb":"","urlbtext":"","targetb":"","urlc":"","urlctext":"","targetc":""}',
    '{"show_title":"","link_titles":"","show_tags":"","show_intro":"","info_block_position":"","info_block_show_title":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_hits":"","show_readmore":"","show_readmore_title":"","readmore_limit":"100","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits_count":"","show_rating":"","show_children":"","show_noauth":"","urls_position":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":"","menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":"","menu_show":"","page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":""}',
    1, 0,
    'uji publik, calon hakim, mahkamah agung, siwas, penilaian masyarakat',
    'Mahkamah Agung RI mengundang masyarakat menilai integritas Calon Hakim dari jalur rekrutmen internal melalui laman Uji Publik. Tindak lanjut Surat Badan Pengawasan MA Nomor 9436/BP/KP1.1.2/XI/2026.',
    1, 0,
    '{"external_url":"https://siwas.mahkamahagung.go.id/Uji_publik","card_image":"images/pengumuman/uji-publik-cakim-2026.webp","source":"Surat Badan Pengawasan MA 9436/BP/KP1.1.2/XI/2026"}',
    0, '*', '', '2026-11-11 09:00:00'
WHERE NOT EXISTS (SELECT 1 FROM #__content WHERE alias = 'pengumuman-uji-publik-calon-hakim-2026');

SET @uji_id := COALESCE(@uji_id, LAST_INSERT_ID());

UPDATE #__content
SET title = 'Pengumuman Uji Publik Calon Hakim Rekrutmen Internal Mahkamah Agung RI',
    introtext = '<p><a href="https://siwas.mahkamahagung.go.id/Uji_publik" target="_blank" rel="noopener noreferrer"><img src="/images/pengumuman/uji-publik-cakim-2026-768.webp" srcset="/images/pengumuman/uji-publik-cakim-2026-480.webp 480w, /images/pengumuman/uji-publik-cakim-2026-768.webp 768w, /images/pengumuman/uji-publik-cakim-2026-1200.webp 1200w, /images/pengumuman/uji-publik-cakim-2026.webp 1414w" sizes="(max-width: 760px) 92vw, 620px" alt="Uji Publik Calon Hakim Rekrutmen Internal Mahkamah Agung RI. Suara Anda, Masa Depan Peradilan. Klik untuk melakukan penilaian." width="768" height="1086" loading="eager" decoding="async"></a></p><p>Mahkamah Agung Republik Indonesia mengundang seluruh lapisan masyarakat untuk memberikan masukan, rekam jejak, dan menilai integritas para Calon Hakim dari jalur rekrutmen internal.</p><p>Mengenal mereka adalah hak Anda. Memastikan mereka berintegritas adalah tanggung jawab kita bersama.</p><p>Banner di atas dapat diklik dan langsung menuju laman resmi Uji Publik. Informasi ini disebarluaskan menindaklanjuti Surat Badan Pengawasan Mahkamah Agung Nomor 9436/BP/KP1.1.2/XI/2026 tanggal 11 November 2026.</p><p><a class="uji-publik-cta" href="https://siwas.mahkamahagung.go.id/Uji_publik" target="_blank" rel="noopener noreferrer">Berikan penilaian di laman Uji Publik &rarr;</a></p>',
    images = '{"image_intro":"","image_fulltext":"","float_intro":"","float_fulltext":"","image_intro_alt":"","image_fulltext_alt":""}',
    metadata = '{"external_url":"https://siwas.mahkamahagung.go.id/Uji_publik","card_image":"images/pengumuman/uji-publik-cakim-2026.webp","source":"Surat Badan Pengawasan MA 9436/BP/KP1.1.2/XI/2026"}',
    state = 1,
    catid = 13,
    access = 1,
    language = '*',
    publish_up = '2026-11-11 09:00:00',
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'pengumuman-uji-publik-calon-hakim-2026';
