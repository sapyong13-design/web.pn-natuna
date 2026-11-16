-- Noki Suryatno holds two posts, so he has to be listed under both.
--
-- He is a PPPK assigned to Subbagian Kepegawaian, Organisasi, dan Tata Laksana *and* to
-- Kepaniteraan Khusus Perikanan. The org chart already carried him only under Kasubbag
-- Kepegawaian, and "Profil Kepaniteraan" listed him nowhere at all, so the two pages
-- disagreed. He is already present in:
--
--   id  60  Profil Kesekretariatan        -> Staf Kesekretariatan
--   id 110  Kepaniteraan Khusus Perikanan -> staff card
--   id 212  Subbagian Kepegawaian         -> Staf Subbagian
--
-- This adds the missing entry to id 59 (Profil Kepaniteraan, "Staf Kepaniteraan"), which is
-- what the unit index and the four sub-pages are reached from. The card is copied verbatim
-- from id 110 so photo, degree badge, status pill and NIP stay in step.
--
-- Literal-only REPLACE, guarded by `NOT LIKE`: the search text is not a substring of the
-- replacement, and once the card is present the guard stops the statement, so replay is a
-- no-op.

-- 1. Append the card to the staff grid.

UPDATE #__content
SET introtext = REPLACE(introtext,
        'alt="Ardiansyah" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199001062025211027</dd></div></dl></div></article></div></section>',
        'alt="Ardiansyah" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199001062025211027</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/noki.webp" alt="Noki Suryatno" loading="lazy"><span class="roster-degree">SMK</span></div><div class="roster-body"><h3 class="roster-name">Noki Suryatno</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199011052025211052</dd></div></dl></div></article></div></section>'),
    `fulltext` = REPLACE(`fulltext`,
        'alt="Ardiansyah" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199001062025211027</dd></div></dl></div></article></div></section>',
        'alt="Ardiansyah" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199001062025211027</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/noki.webp" alt="Noki Suryatno" loading="lazy"><span class="roster-degree">SMK</span></div><div class="roster-body"><h3 class="roster-name">Noki Suryatno</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199011052025211052</dd></div></dl></div></article></div></section>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%alt="Ardiansyah" loading="lazy"%'
  AND introtext NOT LIKE '%Noki Suryatno%';

-- 2. Keep the section counter honest.

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<h2>Staf Kepaniteraan</h2><span class="roster-count">7 orang</span>',
        '<h2>Staf Kepaniteraan</h2><span class="roster-count">8 orang</span>'),
    `fulltext` = REPLACE(`fulltext`,
        '<h2>Staf Kepaniteraan</h2><span class="roster-count">7 orang</span>',
        '<h2>Staf Kepaniteraan</h2><span class="roster-count">8 orang</span>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<h2>Staf Kepaniteraan</h2><span class="roster-count">7 orang</span>%';
