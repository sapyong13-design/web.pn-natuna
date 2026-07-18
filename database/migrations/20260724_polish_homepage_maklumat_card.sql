-- Keep the homepage Maklumat section compact: template chrome supplies the
-- "Layanan Publik" kicker, while document content supplies the service names.
UPDATE #__modules
SET showtitle = 0
WHERE id = 808
  AND position = 'home-alerts';
