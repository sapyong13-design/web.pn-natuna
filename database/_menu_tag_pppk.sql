-- Menu baru "Profil PPPK" (anak Tentang Pengadilan, setelah Profil Kesekretariatan) + tag Joomla "PPPK"
UPDATE pnn_menu SET rgt = rgt + 2 WHERE rgt > 1027;
UPDATE pnn_menu SET lft = lft + 2 WHERE lft > 1027;
INSERT INTO pnn_menu (menutype, title, alias, note, path, link, type, published, parent_id, level, component_id, checked_out, checked_out_time, browserNav, access, img, template_style_id, params, lft, rgt, home, language, client_id, publish_up, publish_down)
VALUES ('mainmenu', 'Profil PPPK', 'profil-pppk', 'pn-natuna-production-menu', 'profil-pengadilan/profil-pppk', 'index.php?option=com_content&view=article&id=114', 'component', 1, 102, 2, 19, NULL, NULL, 0, 1, '', 0, '{"show_hits": "0", "show_tags": "0", "show_intro": "1", "show_title": "1", "link_author": "0", "link_titles": "0", "show_author": "0", "show_noauth": "0", "link_category": "0", "show_category": "0", "show_create_date": "0", "show_modify_date": "0", "show_associations": "0", "show_publish_date": "0", "info_block_position": "0", "link_parent_category": "0", "show_item_navigation": "0", "show_parent_category": "0", "info_block_show_title": "0"}', 1028, 1029, 0, '*', 0, NULL, NULL);

UPDATE pnn_tags SET rgt = 3 WHERE id = 1;
INSERT INTO pnn_tags (parent_id, lft, rgt, level, path, title, alias, note, description, published, checked_out, checked_out_time, access, params, metadesc, metakey, metadata, created_user_id, created_time, created_by_alias, modified_user_id, modified_time, images, urls, hits, language, version, publish_up, publish_down)
VALUES (1, 1, 2, 1, 'pppk', 'PPPK', 'pppk', '', '', 1, NULL, NULL, 1, '{}', '', '', '{}', (SELECT MIN(id) FROM pnn_users), NOW(), '', 0, NOW(), '{}', '{}', 0, '*', 1, NULL, NULL);
