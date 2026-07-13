-- Converge any legacy public AMPUH aliases to one public root menu entry after Transparansi.
-- Canonical article ID remains dynamic. Joomla alias uniqueness requires id-ID, matching Transparansi.

SET @ampuh_article_id := (SELECT id FROM #__content WHERE alias = 'ampuh-2026' AND catid = 9 ORDER BY id ASC LIMIT 1);
SET @content_component_id := (SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component' ORDER BY extension_id ASC LIMIT 1);
SET @transparansi_rgt := (SELECT rgt FROM #__menu WHERE id = 108 AND menutype = 'mainmenu' AND parent_id = 1 LIMIT 1);

-- A prior implementation could have created AMPUH in another language or at an arbitrary root position.
-- Remove every public candidate, then insert one normalized node at Transparansi's right boundary.
DELETE FROM #__menu
WHERE menutype = 'mainmenu' AND alias = 'ampuh' AND parent_id = 1 AND client_id = 0;

UPDATE #__menu
SET lft = lft + 2
WHERE lft > @transparansi_rgt;
UPDATE #__menu
SET rgt = rgt + 2
WHERE rgt > @transparansi_rgt;

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
WHERE @ampuh_article_id IS NOT NULL AND @content_component_id IS NOT NULL AND @transparansi_rgt IS NOT NULL;
