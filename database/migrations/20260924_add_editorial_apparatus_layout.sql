-- Add stable editorial layout hooks to apparatus profiles without changing official personnel data.
UPDATE #__content
SET introtext = CONCAT('<div class="apparatus-editorial apparatus-directory">', introtext, '</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias IN ('profil-hakim','profil-kepaniteraan','profil-kesekretariatan')
  AND introtext NOT LIKE '<div class="apparatus-editorial%';

UPDATE #__content
SET introtext = CONCAT('<div class="apparatus-editorial apparatus-unit">', introtext, '</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias IN ('kepaniteraan-pidana','kepaniteraan-perdata','kepaniteraan-hukum','kepaniteraan-khusus-perikanan','subbagian-kepegawaian-ortala','subbagian-ptip','subbagian-umum-keuangan')
  AND introtext NOT LIKE '<div class="apparatus-editorial%';

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<section class="roster-section"><div class="roster-section-head"><h2>Staf ',
        '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Staf '),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias IN ('profil-kepaniteraan','profil-kesekretariatan')
  AND introtext LIKE '%<h2>Staf %'
  AND introtext NOT LIKE '%roster-section-staff%';

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<section class="roster-section"><div class="roster-section-head"><h2>Hakim Ad-Hoc Perikanan',
        '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Hakim Ad-Hoc Perikanan'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias='profil-hakim'
  AND introtext LIKE '%<h2>Hakim Ad-Hoc Perikanan%'
  AND introtext NOT LIKE '%roster-section-staff%';
