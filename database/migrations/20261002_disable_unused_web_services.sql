-- Situs publik PN Natuna tidak memiliki konsumen Joomla Web Services.
-- Menonaktifkan permukaan API mengurangi jalur autentikasi dan operasi tulis yang
-- tidak dibutuhkan. .htaccess juga menolak /api/ sebelum request mencapai PHP.
-- Aktifkan kembali hanya melalui migrasi baru setelah client, scope, dan owner
-- API didokumentasikan serta diuji.

UPDATE #__extensions
SET enabled = 0
WHERE type = 'plugin'
  AND folder IN ('webservices', 'api-authentication');

UPDATE #__extensions
SET enabled = 0
WHERE type = 'plugin'
  AND folder = 'user'
  AND element = 'token';
