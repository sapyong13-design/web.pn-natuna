-- Queue public URL invalidations after article saves. API credentials remain outside Joomla.
INSERT INTO #__extensions
  (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, ordering, state)
SELECT
  0, 'plg_system_pnnatunacloudflare', 'plugin', 'pnnatunacloudflare', 'system', 0, 1, 1, 0, 0,
  '{"name":"plg_system_pnnatunacloudflare","type":"plugin","creationDate":"2026-08","author":"Pengadilan Negeri Natuna","version":"1.0.0","description":"Queue Cloudflare purge intents","group":"system","filename":"pnnatunacloudflare"}',
  '{}', '', 99, 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM #__extensions
  WHERE type='plugin' AND folder='system' AND element='pnnatunacloudflare'
);

UPDATE #__extensions
SET enabled=1, state=0
WHERE type='plugin' AND folder='system' AND element='pnnatunacloudflare';
