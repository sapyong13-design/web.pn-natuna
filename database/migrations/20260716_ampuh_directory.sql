-- Create canonical public AMPUH directory article and route.
-- Idempotent canonical keys: article alias/category and hidden root menu alias/language.

INSERT INTO #__content (
    asset_id, title, alias, introtext, `fulltext`, state, catid, created, created_by,
    created_by_alias, modified, modified_by, images, urls, attribs, version, ordering,
    metakey, metadesc, access, hits, metadata, featured, language, note
)
SELECT
    0, 'AMPUH 2026 Checklist', 'ampuh-2026',
    '<p>Direktori dokumen AMPUH 2026 Pengadilan Negeri Natuna.</p>', '',
    1, 9, NOW(), 0, '', NOW(), 0, '', '', '{"show_title":"0"}', 1, 0,
    '', 'Direktori dokumen AMPUH 2026 Pengadilan Negeri Natuna.', 1, 0, '{}', 0, '*', ''
WHERE NOT EXISTS (
    SELECT 1 FROM #__content WHERE alias = 'ampuh-2026' AND catid = 9
);

UPDATE #__content
SET title = 'AMPUH 2026 Checklist', introtext = '<p>Direktori dokumen AMPUH 2026 Pengadilan Negeri Natuna.</p>',
    `fulltext` = '', state = 1, access = 1, language = '*', attribs = '{"show_title":"0"}',
    metadesc = 'Direktori dokumen AMPUH 2026 Pengadilan Negeri Natuna.', modified = NOW(), modified_by = 0
WHERE alias = 'ampuh-2026' AND catid = 9;

INSERT INTO #__menu (
    menutype, title, alias, note, path, link, type, published, parent_id, level,
    component_id, checked_out, checked_out_time, browserNav, access, img,
    template_style_id, params, lft, rgt, home, language, client_id
)
SELECT
    'hidden', 'AMPUH 2026 Checklist', 'ampuh', '', 'ampuh',
    CONCAT('index.php?option=com_content&view=article&id=', content.id),
    'component', 1, 1, 1, component.extension_id, NULL, NULL, 0, 1, '',
    0, '{"show_title":"0"}', 0, 0, 0, '*', 0
FROM #__content AS content
INNER JOIN #__extensions AS component ON component.element = 'com_content' AND component.type = 'component'
WHERE content.alias = 'ampuh-2026' AND content.catid = 9
  AND NOT EXISTS (
      SELECT 1 FROM #__menu
      WHERE alias = 'ampuh' AND menutype = 'hidden' AND parent_id = 1 AND language = '*' AND client_id = 0
  );

UPDATE #__menu AS menu
INNER JOIN #__content AS content ON content.alias = 'ampuh-2026' AND content.catid = 9
INNER JOIN #__extensions AS component ON component.element = 'com_content' AND component.type = 'component'
SET menu.title = 'AMPUH 2026 Checklist', menu.note = '', menu.path = 'ampuh',
    menu.link = CONCAT('index.php?option=com_content&view=article&id=', content.id), menu.type = 'component',
    menu.published = 1, menu.level = 1, menu.component_id = component.extension_id, menu.browserNav = 0,
    menu.access = 1, menu.img = '', menu.template_style_id = 0, menu.params = '{"show_title":"0"}', menu.home = 0
WHERE menu.alias = 'ampuh' AND menu.menutype = 'hidden' AND menu.parent_id = 1
  AND menu.language = '*' AND menu.client_id = 0;
