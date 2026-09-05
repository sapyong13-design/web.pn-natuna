-- Cache eligibility now depends on the completed response body, never early rendering.
UPDATE #__extensions
SET params = JSON_REMOVE(
    COALESCE(NULLIF(params, ''), '{}'),
    '$.beforeRender'
)
WHERE type = 'component'
  AND element = 'com_lscache';

-- Record authentic CSRF token preservation and token-bearing response exclusions.
UPDATE #__extensions
SET manifest_cache = JSON_SET(
    COALESCE(NULLIF(manifest_cache, ''), '{}'),
    '$.version', '1.5.2-pn.4'
)
WHERE type = 'plugin'
  AND folder = 'system'
  AND element = 'lscache';
