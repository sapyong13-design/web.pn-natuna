-- Ad-hoc fisheries judges use the same card scale as career judges; the compact staff variant stays with registry and secretariat staff only.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Hakim Ad-Hoc Perikanan',
        '<section class="roster-section"><div class="roster-section-head"><h2>Hakim Ad-Hoc Perikanan'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'profil-hakim'
  AND introtext LIKE '%roster-section-staff%';
