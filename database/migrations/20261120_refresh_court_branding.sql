-- Segarkan URL aset PN Natuna pada modul header/footer yang disimpan di Joomla.
-- Pola dengan kutip/spasi hanya mencocokkan URL yang belum memakai versi baru.
UPDATE #__modules
SET content = REPLACE(REPLACE(REPLACE(
    content,
    '/images/brand/logo-pn-natuna.webp"', '/images/brand/logo-pn-natuna.webp?v=20261120-logo"'),
    '/images/brand/logo-pn-natuna.webp 179w', '/images/brand/logo-pn-natuna.webp?v=20261120-logo 179w'),
    '/images/brand/logo-pn-natuna-96.webp?v=20260811-alpha 96w', '/images/brand/logo-pn-natuna-96.webp?v=20261120-logo 96w')
WHERE (id = 110 AND position = 'header-brand') OR (id = 117 AND position = 'footer-contact');

-- Atum mendukung logo kustom lewat style; tidak perlu mengubah templat inti Joomla.
UPDATE #__template_styles
SET params = JSON_SET(params,
    '$.loginLogo', 'images/brand/logo-pn-natuna.webp',
    '$.loginLogoAlt', 'Pengadilan Negeri Natuna Kelas II',
    '$.logoBrandLarge', 'images/brand/logo-pn-natuna.webp',
    '$.logoBrandLargeAlt', 'Pengadilan Negeri Natuna Kelas II',
    '$.logoBrandSmall', 'images/brand/logo-pn-natuna-96.webp',
    '$.logoBrandSmallAlt', 'Pengadilan Negeri Natuna Kelas II')
WHERE client_id = 1 AND template = 'atum' AND home = '1';

-- Segarkan bagan aktif, termasuk srcset dan tautan gambar ukuran penuh.
UPDATE #__content
SET introtext = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    introtext,
    'struktur-organisasi-2026.png"', 'struktur-organisasi-2026.png?v=20261120-logo"'),
    'struktur-organisasi-2026.png 2245w', 'struktur-organisasi-2026.png?v=20261120-logo 2245w'),
    'struktur-organisasi-2026-480.webp 480w', 'struktur-organisasi-2026-480.webp?v=20261120-logo 480w'),
    'struktur-organisasi-2026-960.webp 960w', 'struktur-organisasi-2026-960.webp?v=20261120-logo 960w'),
    'struktur-organisasi-2026-1500.webp 1500w', 'struktur-organisasi-2026-1500.webp?v=20261120-logo 1500w')
WHERE alias = 'struktur-organisasi' AND introtext LIKE '%struktur-organisasi-2026.png"%';
