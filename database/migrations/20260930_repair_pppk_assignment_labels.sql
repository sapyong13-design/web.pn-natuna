-- Repair the PPPK role rows damaged by 20260928: MySQL REGEXP_REPLACE does not expand a \1 backreference,
-- so every PPPK assignment label was written as the literal text "1" and 20260929 then propagated one damaged row.
-- Each card is restored from its published assignment, anchored on the person's own name so no other card is touched.

-- profil-kepaniteraan: Yuningsih
SET @old := '<h3 class="roster-name">Yuningsih</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Yuningsih</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Pidana</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kepaniteraan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kepaniteraan: Kartina
SET @old := '<h3 class="roster-name">Kartina</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Kartina</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Perdata</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kepaniteraan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kepaniteraan: Ardiansyah
SET @old := '<h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kepaniteraan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Bait, S.H.
SET @old := '<h3 class="roster-name">Bait, S.H.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<h3 class="roster-name">Bait, S.H.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Perencanaan, TI, dan Pelaporan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Rati Pusita, S.Pd.I.
SET @old := '<h3 class="roster-name">Rati Pusita, S.Pd.I.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<h3 class="roster-name">Rati Pusita, S.Pd.I.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Ria Angelina Sitompul
SET @old := '<h3 class="roster-name">Ria Angelina Sitompul</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<h3 class="roster-name">Ria Angelina Sitompul</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Riko Gustianto
SET @old := '<h3 class="roster-name">Riko Gustianto</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<h3 class="roster-name">Riko Gustianto</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Kusnaidi
SET @old := '<h3 class="roster-name">Kusnaidi</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<h3 class="roster-name">Kusnaidi</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-pidana: Yuningsih
SET @old := '<h3 class="roster-name">Yuningsih</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Yuningsih</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Pidana</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-pidana'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-perdata: Kartina
SET @old := '<h3 class="roster-name">Kartina</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Kartina</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Perdata</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-perdata'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-hukum: Ardiansyah
SET @old := '<h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Kepaniteraan Hukum</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-hukum'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-ptip: Bait, S.H.
SET @old := '<h3 class="roster-name">Bait, S.H.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Bait, S.H.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Perencanaan, TI, dan Pelaporan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-ptip'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-umum-keuangan: Rati Pusita, S.Pd.I.
SET @old := '<h3 class="roster-name">Rati Pusita, S.Pd.I.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Rati Pusita, S.Pd.I.</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-umum-keuangan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-umum-keuangan: Ria Angelina Sitompul
SET @old := '<h3 class="roster-name">Ria Angelina Sitompul</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Ria Angelina Sitompul</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-umum-keuangan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-umum-keuangan: Riko Gustianto
SET @old := '<h3 class="roster-name">Riko Gustianto</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Riko Gustianto</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-umum-keuangan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-umum-keuangan: Kusnaidi
SET @old := '<h3 class="roster-name">Kusnaidi</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<h3 class="roster-name">Kusnaidi</h3><div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Umum dan Keuangan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-umum-keuangan'
  AND introtext LIKE CONCAT('%', @old, '%');
