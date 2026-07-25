-- Replace homepage service assets with optimized WebP files. Source tokens cannot occur in replacements.
UPDATE #__modules
SET content = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(content,
                    'logo-eberpadu.png', 'logo-eberpadu.webp'),
                'logo-ecourt.png', 'logo-ecourt.webp'),
            'logo-direktori-putusan.png', 'logo-direktori-putusan.webp'),
        'https://esurvey.badilum.mahkamahagung.go.id/assets/img/sisuper.png',
        '/images/layanan/logo-sisuper.webp'),
    'https://eksekusi.badilum.mahkamahagung.go.id/assets/img/perkusi2.png',
    '/images/layanan/logo-perkusi.webp')
WHERE id = 112
  AND (content LIKE '%logo-eberpadu.png%'
       OR content LIKE '%logo-ecourt.png%'
       OR content LIKE '%logo-direktori-putusan.png%'
       OR content LIKE '%https://esurvey.badilum.mahkamahagung.go.id/assets/img/sisuper.png%'
       OR content LIKE '%https://eksekusi.badilum.mahkamahagung.go.id/assets/img/perkusi2.png%');

UPDATE #__modules
SET content = REPLACE(content,
    'maklumat-layanan-informasi-publik.png',
    'maklumat-layanan-informasi-publik.webp')
WHERE id = 808
  AND content LIKE '%maklumat-layanan-informasi-publik.png%';

UPDATE #__content
SET introtext = REPLACE(introtext,
    'maklumat-layanan-informasi-publik.png',
    'maklumat-layanan-informasi-publik.webp'),
    `fulltext` = REPLACE(`fulltext`,
    'maklumat-layanan-informasi-publik.png',
    'maklumat-layanan-informasi-publik.webp'),
    modified = UTC_TIMESTAMP()
WHERE id = 19
  AND (introtext LIKE '%maklumat-layanan-informasi-publik.png%'
       OR `fulltext` LIKE '%maklumat-layanan-informasi-publik.png%');
