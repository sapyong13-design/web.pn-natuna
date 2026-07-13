-- Converge public AMPUH aliases to a retained root menu item immediately after Transparansi.
-- Rebuild only mainmenu intervals deterministically, preserving existing menu IDs and hidden rows.

SET @ampuh_article_id := (SELECT id FROM #__content WHERE alias = 'ampuh-2026' AND catid = 9 ORDER BY id ASC LIMIT 1);
SET @content_component_id := (SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component' ORDER BY extension_id ASC LIMIT 1);
SET @transparansi_id := (SELECT id FROM #__menu WHERE id = 108 AND menutype = 'mainmenu' AND parent_id = 1 LIMIT 1);

INSERT INTO #__menu (
    menutype, title, alias, note, path, link, type, published, parent_id, level,
    component_id, checked_out, checked_out_time, browserNav, access, img,
    template_style_id, params, lft, rgt, home, language, client_id
)
SELECT
    'mainmenu', 'AMPUH', 'ampuh', '', 'ampuh',
    CONCAT('index.php?option=com_content&view=article&id=', @ampuh_article_id),
    'component', 1, 1, 1, @content_component_id, NULL, NULL, 0, 1, '',
    0, '{"show_title":"0"}', 0, 0, 0, 'id-ID', 0
WHERE @ampuh_article_id IS NOT NULL
  AND @content_component_id IS NOT NULL
  AND @transparansi_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM #__menu
      WHERE menutype = 'mainmenu' AND parent_id = 1 AND alias = 'ampuh' AND client_id = 0
  );

SET @ampuh_id := (
    SELECT id FROM #__menu
    WHERE menutype = 'mainmenu' AND parent_id = 1 AND alias = 'ampuh' AND client_id = 0
    ORDER BY id ASC LIMIT 1
);
SET @transparansi_lft := (SELECT lft FROM #__menu WHERE id = @transparansi_id);

CREATE TEMPORARY TABLE ampuh_duplicate_tree (id INT NOT NULL PRIMARY KEY);

INSERT INTO ampuh_duplicate_tree (id)
WITH RECURSIVE descendants AS (
    SELECT id
    FROM #__menu
    WHERE menutype = 'mainmenu' AND parent_id = 1 AND alias = 'ampuh' AND client_id = 0 AND id <> @ampuh_id
    UNION ALL
    SELECT child.id
    FROM #__menu AS child
    INNER JOIN descendants AS parent ON child.parent_id = parent.id
    WHERE child.menutype = 'mainmenu'
)
SELECT id FROM descendants;

DELETE menu_item
FROM #__menu AS menu_item
INNER JOIN ampuh_duplicate_tree AS duplicate_item ON duplicate_item.id = menu_item.id;

DROP TEMPORARY TABLE ampuh_duplicate_tree;

-- Give the retained root a stable sort point. Remaining roots after Transparansi move aside;
-- children retain their relative order and are included by the rebuild below.
UPDATE #__menu
SET lft = lft + 1000000, rgt = rgt + 1000000
WHERE menutype = 'mainmenu' AND parent_id = 1 AND id <> @ampuh_id AND lft > @transparansi_lft;

UPDATE #__menu
SET parent_id = 1, level = 1, lft = @transparansi_lft + 1, rgt = @transparansi_lft + 2,
    title = 'AMPUH', alias = 'ampuh', note = '', path = 'ampuh',
    link = CONCAT('index.php?option=com_content&view=article&id=', @ampuh_article_id),
    type = 'component', published = 1, component_id = @content_component_id,
    checked_out = NULL, checked_out_time = NULL, browserNav = 0, access = 1,
    img = '', template_style_id = 0, params = '{"show_title":"0"}', home = 0,
    language = 'id-ID', client_id = 0
WHERE id = @ampuh_id
  AND @ampuh_article_id IS NOT NULL AND @content_component_id IS NOT NULL AND @transparansi_id IS NOT NULL;
CREATE TEMPORARY TABLE ampuh_menu_bounds (id INT NOT NULL PRIMARY KEY, lft INT NOT NULL, rgt INT NOT NULL, level INT NOT NULL);

INSERT INTO ampuh_menu_bounds (id, lft, rgt, level)
WITH RECURSIVE menu_tree AS (
    SELECT id, parent_id, CAST(CONCAT(LPAD(lft, 10, '0'), ':', LPAD(id, 10, '0')) AS CHAR(1000)) AS sort_path
    FROM #__menu
    WHERE id = 1
    UNION ALL
    SELECT child.id, child.parent_id, CONCAT(parent.sort_path, '/', LPAD(child.lft, 10, '0'), ':', LPAD(child.id, 10, '0'))
    FROM #__menu AS child
    INNER JOIN menu_tree AS parent ON child.parent_id = parent.id
), events AS (
    SELECT id, CONCAT(sort_path, '/0') AS event_path, 'open' AS event_type FROM menu_tree
    UNION ALL
    SELECT id, CONCAT(sort_path, '/z') AS event_path, 'close' AS event_type FROM menu_tree
), numbered_events AS (
    SELECT id, event_type, ROW_NUMBER() OVER (ORDER BY event_path) AS boundary FROM events
)
SELECT node.id,
       MAX(CASE WHEN event_type = 'open' THEN boundary END),
       MAX(CASE WHEN event_type = 'close' THEN boundary END),
       LENGTH(node.sort_path) - LENGTH(REPLACE(node.sort_path, '/', ''))
FROM menu_tree AS node
INNER JOIN numbered_events ON numbered_events.id = node.id
GROUP BY node.id, node.sort_path;

UPDATE #__menu AS menu_item
INNER JOIN ampuh_menu_bounds AS bounds ON bounds.id = menu_item.id
SET menu_item.lft = bounds.lft, menu_item.rgt = bounds.rgt, menu_item.level = bounds.level;

DROP TEMPORARY TABLE ampuh_menu_bounds;
