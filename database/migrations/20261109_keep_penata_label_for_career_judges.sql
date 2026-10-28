-- Keep the "Penata" wording for the four career judges on the "Profil Hakim" roster.
-- Golongan stays III.b; only the rank label loses the "Muda Tingkat I" prefix.
-- Scoped per roster card (name heading + the Pangkat/Gol. field inside the same card),
-- so Salihin Ardiansyah and the chief judge keep their current values.
-- Idempotent: after the rewrite the source pattern no longer matches.
--
-- The rendered card is `<h3 class="roster-name">NAME</h3> ... <dt>Pangkat/Gol.</dt><dd>RANK</dd>`,
-- so the value never sits directly after the label; the pattern has to cross `</dt><dd>`.

UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?<dt>Pangkat/Gol[.]</dt><dd>)Penata Muda Tingkat I / III[.]b(</dd>)',
        '$1Penata / III.b$2'
    ),
    `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?<dt>Pangkat/Gol[.]</dt><dd>)Penata Muda Tingkat I / III[.]b(</dd>)',
        '$1Penata / III.b$2'
    ),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%<dd>Penata Muda Tingkat I / III.b</dd>%';
