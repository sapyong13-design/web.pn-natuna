-- Correct the rank of career judges on the "Profil Hakim" roster article.
-- Salihin Ardiansyah stays at Penata / III.c; the chief judge stays at Pembina / IV.a.
-- Only the four remaining career judges move from Penata / III.c to Penata Muda Tingkat I / III.b.
-- Scoped per roster card (name heading + the Pangkat/Gol. field inside the same card) so the
-- rank of every other person on the page is left untouched. Idempotent: after the rewrite the
-- pattern no longer matches.
--
-- The rendered card is `<h3 class="roster-name">NAME</h3> ... <dt>Pangkat/Gol.</dt><dd>RANK</dd>`,
-- so the value never sits directly after the label; the pattern has to cross `</dt><dd>`.

UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?<dt>Pangkat/Gol[.]</dt><dd>)Penata / III[.]c(</dd>)',
        '$1Penata Muda Tingkat I / III.b$2'
    ),
    `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?s)(<h3 class="roster-name">(?:Alfariz Maulana Reza|Geraldo Gracelo Mario Situmeang|Haditio|Swandi Hutabarat)[^<]*</h3>.{0,500}?<dt>Pangkat/Gol[.]</dt><dd>)Penata / III[.]c(</dd>)',
        '$1Penata Muda Tingkat I / III.b$2'
    ),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%<dd>Penata / III.c</dd>%';
