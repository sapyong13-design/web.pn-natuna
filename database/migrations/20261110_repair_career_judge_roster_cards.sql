-- Repair the "Profil Hakim" roster cards that 20261108 destroyed on MariaDB.
--
-- 20261108 and 20261109 use REGEXP_REPLACE with a `$1` backreference. MySQL 8 expands it,
-- MariaDB does not: it writes `$1` literally and the whole match — heading, role, NIP and the
-- Pangkat/Gol. field — collapses into `$1Penata Muda Tingkat I / III.b`. Those two migrations
-- are already applied and the runner refuses a changed checksum, so the damage is undone here
-- instead of by editing them.
--
-- Literal-only: no backreferences, no `\s`, so it behaves identically on MySQL 8 and MariaDB.
-- The damaged card keeps the original closing `</div>` of the Pangkat field, so the pattern
-- consumes that one tag and the replacement ends at the rebuilt Pangkat field; the remaining
-- `</div>` still closes `.roster-body`.
--
-- Repairs two states, and is a no-op on a healthy article:
--   1. broken body : `<div class="roster-body"> $1Penata Muda Tingkat I / III.b</div>`
--   2. wrong label : `<div>Pangkat/Gol.Penata Muda Tingkat I / III.b</div>` (production markup)
--                    `<dt>Pangkat/Gol.</dt><dd>Penata Muda Tingkat I / III.b</dd>` (rebuild markup)
-- Final label is Penata / III.b; Salihin Ardiansyah keeps Penata / III.c.

-- 1. Rebuild the four destroyed cards from their photo anchor.

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext,
        'alt="Alfariz Maulana Reza, S[.]H[.], M[.]H[.]" loading="lazy"><span class="roster-degree">S2</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Alfariz Maulana Reza, S.H., M.H." loading="lazy"><span class="roster-degree">S2</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Alfariz Maulana Reza, S.H., M.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199712262022031005</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    `fulltext` = REGEXP_REPLACE(`fulltext`,
        'alt="Alfariz Maulana Reza, S[.]H[.], M[.]H[.]" loading="lazy"><span class="roster-degree">S2</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Alfariz Maulana Reza, S.H., M.H." loading="lazy"><span class="roster-degree">S2</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Alfariz Maulana Reza, S.H., M.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199712262022031005</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%$1Penata Muda Tingkat I / III.b%';

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext,
        'alt="Geraldo Gracelo Mario Situmeang, S[.]H[.], M[.]H[.]" loading="lazy"><span class="roster-degree">S2</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Geraldo Gracelo Mario Situmeang, S.H., M.H." loading="lazy"><span class="roster-degree">S2</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Geraldo Gracelo Mario Situmeang, S.H., M.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199906042022031008</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    `fulltext` = REGEXP_REPLACE(`fulltext`,
        'alt="Geraldo Gracelo Mario Situmeang, S[.]H[.], M[.]H[.]" loading="lazy"><span class="roster-degree">S2</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Geraldo Gracelo Mario Situmeang, S.H., M.H." loading="lazy"><span class="roster-degree">S2</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Geraldo Gracelo Mario Situmeang, S.H., M.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199906042022031008</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%$1Penata Muda Tingkat I / III.b%';

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext,
        'alt="Haditio, S[.]H[.]" loading="lazy"><span class="roster-degree">S1</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Haditio, S.H." loading="lazy"><span class="roster-degree">S1</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Haditio, S.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199702172022031003</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    `fulltext` = REGEXP_REPLACE(`fulltext`,
        'alt="Haditio, S[.]H[.]" loading="lazy"><span class="roster-degree">S1</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Haditio, S.H." loading="lazy"><span class="roster-degree">S1</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Haditio, S.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199702172022031003</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%$1Penata Muda Tingkat I / III.b%';

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext,
        'alt="Swandi Hutabarat, S[.]H[.]" loading="lazy"><span class="roster-degree">S1</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Swandi Hutabarat, S.H." loading="lazy"><span class="roster-degree">S1</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Swandi Hutabarat, S.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199703182022031007</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    `fulltext` = REGEXP_REPLACE(`fulltext`,
        'alt="Swandi Hutabarat, S[.]H[.]" loading="lazy"><span class="roster-degree">S1</span></div>[[:space:]]*<div class="roster-body">[[:space:]]*[$]1Penata Muda Tingkat I / III[.]b[[:space:]]*</div>',
        'alt="Swandi Hutabarat, S.H." loading="lazy"><span class="roster-degree">S1</span></div>\r\n<div class="roster-body">\r\n<h3 class="roster-name">Swandi Hutabarat, S.H.</h3>\r\n<span class="roster-role">Hakim</span>\r\n<div>NIP199703182022031007</div>\r\n<div>Pangkat/Gol.Penata / III.b</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%$1Penata Muda Tingkat I / III.b%';

-- 2. Drop the "Muda Tingkat I" prefix wherever a card still carries it. Only the four career
-- judges ever hold that value in this article; Salihin Ardiansyah is already Penata / III.c.

UPDATE #__content
SET introtext = REPLACE(introtext, '<div>Pangkat/Gol.Penata Muda Tingkat I / III.b</div>', '<div>Pangkat/Gol.Penata / III.b</div>'),
    `fulltext` = REPLACE(`fulltext`, '<div>Pangkat/Gol.Penata Muda Tingkat I / III.b</div>', '<div>Pangkat/Gol.Penata / III.b</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%<div>Pangkat/Gol.Penata Muda Tingkat I / III.b</div>%';

UPDATE #__content
SET introtext = REPLACE(introtext, '<dt>Pangkat/Gol.</dt><dd>Penata Muda Tingkat I / III.b</dd>', '<dt>Pangkat/Gol.</dt><dd>Penata / III.b</dd>'),
    `fulltext` = REPLACE(`fulltext`, '<dt>Pangkat/Gol.</dt><dd>Penata Muda Tingkat I / III.b</dd>', '<dt>Pangkat/Gol.</dt><dd>Penata / III.b</dd>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Hakim'
  AND introtext LIKE '%<dt>Pangkat/Gol.</dt><dd>Penata Muda Tingkat I / III.b</dd>%';
