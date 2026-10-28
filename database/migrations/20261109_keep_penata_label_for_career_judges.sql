-- Keep the "Penata" wording for the four career judges on the "Profil Hakim" roster.
-- Golongan stays III.b; only the rank label loses the "Muda Tingkat I" prefix.
-- Scoped per roster card (name heading + the Pangkat/Gol. field inside the same card),
-- so Salihin Ardiansyah and the chief judge keep their current values.
-- Idempotent: after the rewrite the source pattern no longer matches.
--
-- Two card markups exist for this article across environments:
--   production : <h3 class="roster-name">NAME</h3> ... <div>Pangkat/Gol.Penata Muda Tingkat I / III.b</div>
--   rebuild DB : <h3 class="roster-name">NAME</h3> ... <dt>Pangkat/Gol.</dt><dd>Penata Muda Tingkat I / III.b</dd>
-- so the separator between the label and the value is optional in the pattern.

UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?Pangkat/Gol[.](?:</dt><dd>)?)Penata Muda Tingkat I / III[.]b',
        '$1Penata / III.b'
    ),
    `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?Pangkat/Gol[.](?:</dt><dd>)?)Penata Muda Tingkat I / III[.]b',
        '$1Penata / III.b'
    ),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext REGEXP '(?s)<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?Pangkat/Gol[.](?:</dt><dd>)?Penata Muda Tingkat I / III[.]b';
