-- Pakai aset resolusi penuh untuk semua ukuran tampilan logo; ukuran CSS tetap.
UPDATE #__modules
SET content = REPLACE(REPLACE(
    content,
    ' srcset="/images/brand/logo-pn-natuna-96.webp?v=20261120-logo 96w, /images/brand/logo-pn-natuna.webp?v=20261120-logo 179w" sizes="(max-width: 760px) 42px, 72px"', ''),
    '/images/brand/logo-pn-natuna.webp?v=20261120-logo"', '/images/brand/logo-pn-natuna.webp?v=20261120-fullsize"')
WHERE ((id = 110 AND position = 'header-brand') OR (id = 117 AND position = 'footer-contact'))
  AND content LIKE '%/images/brand/logo-pn-natuna.webp?v=20261120-logo%';

UPDATE #__template_styles
SET params = JSON_SET(params, '$.logoBrandSmall', 'images/brand/logo-pn-natuna.webp')
WHERE client_id = 1 AND template = 'atum' AND home = '1'
  AND JSON_UNQUOTE(JSON_EXTRACT(params, '$.logoBrandSmall')) = 'images/brand/logo-pn-natuna-96.webp';
