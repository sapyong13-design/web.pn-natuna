-- Keep the dynamic homepage fresh while reducing cold misses on public routes.
-- Content/menu saves still purge their LiteSpeed tags immediately.
UPDATE #__extensions
SET params = JSON_SET(
    CASE WHEN JSON_VALID(params) THEN params ELSE JSON_OBJECT() END,
    '$.cacheEnabled', '1',
    '$.cacheTimeout', '120',
    '$.homePageCacheTimeout', '15',
    '$.serveStale', '1',
    '$.autoRecache', '0'
)
WHERE type = 'component'
  AND element = 'com_lscache';
