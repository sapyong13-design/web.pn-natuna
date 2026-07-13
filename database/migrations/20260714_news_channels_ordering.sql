-- Keep public news and announcement listings newest-first and exclude index articles.

UPDATE #__content
SET catid = 2
WHERE id = 53
  AND alias = 'berita-dan-pengumuman-landing';

UPDATE #__content
SET state = 0
WHERE id = 6
  AND alias = 'berita-dan-pengumuman';

UPDATE #__content
SET publish_up = created
WHERE catid IN (12, 13)
  AND (publish_up IS NULL OR publish_up <= '2000-01-02 00:00:00');

UPDATE #__menu
SET params = JSON_SET(
  COALESCE(NULLIF(params, ''), '{}'),
  '$.orderby_pri', 'none',
  '$.orderby_sec', 'rdate',
  '$.order_date', 'published',
  '$.num_leading_articles', '0',
  '$.num_intro_articles', '6',
  '$.num_columns', '3',
  '$.num_links', '0'
)
WHERE id IN (141, 142, 233, 234);
