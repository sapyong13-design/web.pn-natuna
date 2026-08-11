-- Consolidate legacy hidden-menu aliases into the published hierarchical mainmenu URL.
-- Google previously saw both paths as 200 with self-canonicals and sitemap entries.
UPDATE #__extensions SET enabled=1
WHERE type='plugin' AND folder='system' AND element='redirect';

INSERT INTO #__redirect_links
  (old_url,new_url,referer,comment,hits,published,created_date,modified_date)
SELECT
  CONCAT('/', legacy.path),
  CONCAT('/', canonical.path),
  '',
  'Route pendek lama dialihkan ke route hierarkis kanonis.',
  0,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()
FROM #__menu legacy
INNER JOIN #__menu canonical
  ON canonical.link=legacy.link
  AND canonical.client_id=0
  AND canonical.published=1
  AND canonical.menutype='mainmenu'
  AND canonical.path<>legacy.path
WHERE legacy.client_id=0
  AND legacy.published=1
  AND legacy.menutype='hidden'
  AND legacy.type='component'
  AND legacy.link LIKE 'index.php?option=com_content&view=article&id=%'
  AND NOT EXISTS (
    SELECT 1 FROM #__redirect_links existing
    WHERE existing.old_url=CONCAT('/', legacy.path)
  );

UPDATE #__menu legacy
INNER JOIN #__menu canonical
  ON canonical.link=legacy.link
  AND canonical.client_id=0
  AND canonical.published=1
  AND canonical.menutype='mainmenu'
  AND canonical.path<>legacy.path
SET legacy.published=0
WHERE legacy.client_id=0
  AND legacy.published=1
  AND legacy.menutype='hidden'
  AND legacy.type='component'
  AND legacy.link LIKE 'index.php?option=com_content&view=article&id=%';
