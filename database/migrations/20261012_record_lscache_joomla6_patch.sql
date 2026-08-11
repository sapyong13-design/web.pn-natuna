-- The upstream v1.5.2 tag ships a stale 1.5.1 manifest and Joomla 3 global names.
-- The repository plugin adds a narrow Joomla 6 alias shim; record that deployed version.
UPDATE `#__extensions`
SET `manifest_cache` = JSON_SET(
  COALESCE(NULLIF(`manifest_cache`, ''), '{}'),
  '$.version',
  '1.5.2-pn.1'
)
WHERE `type` = 'plugin'
  AND `folder` = 'system'
  AND `element` = 'lscache';
