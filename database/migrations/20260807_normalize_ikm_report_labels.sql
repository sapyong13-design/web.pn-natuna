-- Use IKM consistently in the public-facing archive while preserving the established laporan-skm route alias.
UPDATE #__content
SET title = 'Laporan IKM',
    introtext = REPLACE(REPLACE(REPLACE(introtext,
        '<h2>Laporan SKM / IKM</h2>', '<h2>Laporan IKM</h2>'),
        'Informasi resmi laporan skm / ikm ', 'Informasi resmi laporan IKM '),
        '<strong>Laporan SKM ', '<strong>Laporan IKM '),
    modified = NOW()
WHERE alias = 'laporan-skm';
