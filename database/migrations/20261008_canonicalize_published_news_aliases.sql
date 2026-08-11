-- Remove the import-only `legacy-` marker from published Berita/Pengumuman URLs.
-- Old public paths remain permanent redirects; canonical tags then follow the clean Joomla route.

INSERT INTO #__redirect_links (
    old_url, new_url, referer, comment, hits, published, created_date, modified_date
)
SELECT
    CONCAT('/', c.path, '/', a.alias),
    CONCAT('/', c.path, '/', SUBSTRING(a.alias, 8)),
    '',
    'Alias impor berita dibersihkan; URL lama dialihkan ke route kanonis.',
    0,
    1,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM #__content a
INNER JOIN #__categories c ON c.id = a.catid
LEFT JOIN #__content clash
    ON clash.catid = a.catid
    AND clash.id <> a.id
    AND clash.alias = SUBSTRING(a.alias, 8)
WHERE c.path IN ('berita', 'pengumuman')
  AND a.state = 1
  AND a.alias LIKE 'legacy-%'
  AND clash.id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM (SELECT old_url FROM #__redirect_links) existing
      WHERE existing.old_url = CONCAT('/', c.path, '/', a.alias)
  );

UPDATE #__content a
INNER JOIN #__categories c ON c.id = a.catid
LEFT JOIN #__content clash
    ON clash.catid = a.catid
    AND clash.id <> a.id
    AND clash.alias = SUBSTRING(a.alias, 8)
SET a.alias = SUBSTRING(a.alias, 8),
    a.modified = UTC_TIMESTAMP()
WHERE c.path IN ('berita', 'pengumuman')
  AND a.state = 1
  AND a.alias LIKE 'legacy-%'
  AND clash.id IS NULL;

UPDATE #__extensions
SET enabled = 1
WHERE element = 'redirect'
  AND type = 'plugin'
  AND folder = 'system';
