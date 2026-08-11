-- Keep the public purge endpoint private: replace the vendor default token and accept only origin requests.
UPDATE #__extensions
SET params = JSON_SET(
    CASE WHEN JSON_VALID(params) THEN params ELSE JSON_OBJECT() END,
    '$.cleanCache',
    CASE
        WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(params, '$.cleanCache')), '') IN ('', 'purgeAllCache')
            THEN SHA1(CONCAT(UUID(), RAND(), UTC_TIMESTAMP(6)))
        ELSE JSON_UNQUOTE(JSON_EXTRACT(params, '$.cleanCache'))
    END,
    '$.adminIPs',
    CASE
        WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(params, '$.adminIPs')), '') = '' THEN '127.0.0.1'
        ELSE JSON_UNQUOTE(JSON_EXTRACT(params, '$.adminIPs'))
    END
)
WHERE type = 'component'
  AND element = 'com_lscache';

-- Record the origin-only purge hardening shipped by this repository.
UPDATE #__extensions
SET manifest_cache = JSON_SET(
    COALESCE(NULLIF(manifest_cache, ''), '{}'),
    '$.version', '1.5.2-pn.2'
)
WHERE type = 'plugin'
  AND folder = 'system'
  AND element = 'lscache';
