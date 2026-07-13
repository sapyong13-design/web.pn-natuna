-- Add public AMPUH menu directly after Transparansi. Article ID remains canonical and dynamic.
-- The insert point is Transparansi's closing boundary; shifting every later boundary preserves Joomla's nested set.
-- Match Transparansi's id-ID language so hidden canonical route retains its root alias.

SET @ampuh_article_id := (
    SELECT id FROM #__content WHERE alias = 'ampuh-2026' AND catid = 9 ORDER BY id ASC LIMIT 1
);
SET @content_component_id := (
    SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component' ORDER BY extension_id ASC LIMIT 1
);
SET @transparansi_rgt := (
    SELECT rgt FROM #__menu WHERE id = 108 AND menutype = 'mainmenu' AND parent_id = 1 LIMIT 1
);
SET @ampuh_menu_id := (
    SELECT id FROM #__menu
    WHERE menutype = 'mainmenu' AND alias = 'ampuh' AND parent_id = 1 AND language = 'id-ID' AND client_id = 0
    ORDER BY id ASC LIMIT 1
);
SET @ampuh_inserted := @ampuh_menu_id IS NULL;

INSERT INTO #__menu (
    menutype, title, alias, note, path, link, type, published, parent_id, level,
    component_id, checked_out, checked_out_time, browserNav, access, img,
    template_style_id, params, lft, rgt, home, language, client_id
)
SELECT
    'mainmenu', 'AMPUH', 'ampuh', '', 'ampuh',
    CONCAT('index.php?option=com_content&view=article&id=', @ampuh_article_id),
    'component', 1, 1, 1, @content_component_id, NULL, NULL, 0, 1, '',
    0, '{"show_title":"0"}', @transparansi_rgt + 1, @transparansi_rgt + 2, 0, 'id-ID', 0
WHERE @ampuh_menu_id IS NULL AND @ampuh_article_id IS NOT NULL AND @content_component_id IS NOT NULL AND @transparansi_rgt IS NOT NULL;

SET @ampuh_menu_id := (
    SELECT id FROM #__menu
    WHERE menutype = 'mainmenu' AND alias = 'ampuh' AND parent_id = 1 AND language = 'id-ID' AND client_id = 0
    ORDER BY id ASC LIMIT 1
);

UPDATE #__menu
SET title = 'AMPUH', note = '', path = 'ampuh',
    link = CONCAT('index.php?option=com_content&view=article&id=', @ampuh_article_id),
    type = 'component', published = 1, level = 1, component_id = @content_component_id,
    browserNav = 0, access = 1, img = '', template_style_id = 0, params = '{"show_title":"0"}', home = 0
WHERE id = @ampuh_menu_id;

-- Fresh insert starts at Transparansi's former right edge. Shift all existing later nodes and ancestors once.
UPDATE #__menu
SET lft = lft + 2
WHERE @ampuh_inserted = 1 AND lft > @transparansi_rgt AND id <> @ampuh_menu_id;

UPDATE #__menu
SET rgt = rgt + 2
WHERE @ampuh_inserted = 1 AND rgt > @transparansi_rgt AND id <> @ampuh_menu_id;
