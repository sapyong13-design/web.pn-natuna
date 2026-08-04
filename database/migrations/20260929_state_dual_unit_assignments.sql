-- Juprizal and Noki Suryatno serve two units, so every card states both assignments; PPPK status keeps the lead.
-- Names, photos, NIP, and pangkat/golongan stay untouched.

-- profil-kepaniteraan: Juprizal, A.Md., A.B.
SET @old := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kepaniteraan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- profil-kesekretariatan: Noki Suryatno
SET @old := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-pidana: Juprizal, A.Md., A.B.
SET @old := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-pidana'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-khusus-perikanan: Juprizal, A.Md., A.B.
SET @old := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-khusus-perikanan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- kepaniteraan-khusus-perikanan: Noki Suryatno
SET @old := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-khusus-perikanan'
  AND introtext LIKE CONCAT('%', @old, '%');

-- subbagian-kepegawaian-ortala: Noki Suryatno
SET @old := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">1</span></div>';
SET @new := '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">Subbag Kepegawaian, Organisasi, dan Tata Laksana</span><span class="roster-role">Kepaniteraan Khusus Perikanan</span></div>';
UPDATE #__content
SET introtext = REPLACE(introtext, @old, @new),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'subbagian-kepegawaian-ortala'
  AND introtext LIKE CONCAT('%', @old, '%');
