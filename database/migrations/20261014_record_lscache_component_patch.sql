-- Record the Joomla 6-safe no-op frontend component entry point.
UPDATE #__extensions
SET manifest_cache = JSON_SET(
    COALESCE(NULLIF(manifest_cache, ''), '{}'),
    '$.version', '1.5.2-pn.3'
)
WHERE type = 'plugin'
  AND folder = 'system'
  AND element = 'lscache';

-- The legacy Joomla 3 administrator component is not part of the runtime cache path.
-- Keep its unsupported menu hidden; configuration remains managed by migrations.
UPDATE #__menu
SET published = 0
WHERE client_id = 1
  AND component_id = (
      SELECT extension_id
      FROM #__extensions
      WHERE type = 'component'
        AND element = 'com_lscache'
      LIMIT 1
  );
