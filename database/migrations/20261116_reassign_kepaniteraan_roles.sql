-- Reassign the kepaniteraan roles to match the current appointment letter.
--
--   Jhivo Wilanda, S.H.        Panitera Muda Perdata + Plt. Panitera Muda Hukum
--   Ari Putra Utama, A.Md. AB  Panitera Muda Pidana + Plt. Panitera Muda Khusus Perikanan
--   Hadry B., S.H.             Panitera + Jurusita
--
-- Hadry B. keeps the Panitera seat but releases both Panmud titles and the Plt. Jurusita
-- placeholder; the two Panmud posts move to the officers who actually hold them now, so the
-- Perdata and Perikanan sub-pages no longer point at the Panitera.
--
-- Literal-only: no REGEXP_REPLACE, no backreferences, so MySQL 8 and MariaDB behave the same.
-- Every search string is chosen so it is NOT a substring of its replacement; together with the
-- `LIKE`/`NOT LIKE` guards that keeps each statement a no-op once applied, which is what the
-- replay mode needs.

-- 1. Profil Kepaniteraan - the Panitera keeps only "Jurusita".

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<span class="roster-role">Plt. Panitera Muda Perdata</span><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Plt. Jurusita</span>',
        '<span class="roster-role">Jurusita</span>'),
    `fulltext` = REPLACE(`fulltext`,
        '<span class="roster-role">Plt. Panitera Muda Perdata</span><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Plt. Jurusita</span>',
        '<span class="roster-role">Jurusita</span>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<span class="roster-role">Plt. Jurusita</span>%';

-- 2. Profil Kepaniteraan - Jhivo gains Panitera Muda Perdata.

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Hukum</span><span class="roster-role">Panitera Pengganti</span></div>',
        '<div class="roster-role-row"><span class="roster-role">Panitera Muda Perdata</span><span class="roster-role">Plt. Panitera Muda Hukum</span><span class="roster-role">Panitera Pengganti</span></div>'),
    `fulltext` = REPLACE(`fulltext`,
        '<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Hukum</span><span class="roster-role">Panitera Pengganti</span></div>',
        '<div class="roster-role-row"><span class="roster-role">Panitera Muda Perdata</span><span class="roster-role">Plt. Panitera Muda Hukum</span><span class="roster-role">Panitera Pengganti</span></div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Hukum</span><span class="roster-role">Panitera Pengganti</span></div>%';

-- 3. Profil Kepaniteraan - Ari gains Plt. Panitera Muda Khusus Perikanan.

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Pidana</span><span class="roster-role">Panitera Pengganti</span></div>',
        '<div class="roster-role-row"><span class="roster-role">Panitera Muda Pidana</span><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera Pengganti</span></div>'),
    `fulltext` = REPLACE(`fulltext`,
        '<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Pidana</span><span class="roster-role">Panitera Pengganti</span></div>',
        '<div class="roster-role-row"><span class="roster-role">Panitera Muda Pidana</span><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera Pengganti</span></div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Pidana</span><span class="roster-role">Panitera Pengganti</span></div>%';

-- 4. Profil Kepaniteraan - unit index follows the new holders.

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Hadry B., S.H.</span>',
        '<strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Jhivo Wilanda, S.H.</span>'),
    `fulltext` = REPLACE(`fulltext`,
        '<strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Hadry B., S.H.</span>',
        '<strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Jhivo Wilanda, S.H.</span>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Hadry B., S.H.</span>%';

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Hadry B., S.H.</span>',
        '<strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Ari Putra Utama, A.Md. A.B.</span>'),
    `fulltext` = REPLACE(`fulltext`,
        '<strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Hadry B., S.H.</span>',
        '<strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Ari Putra Utama, A.Md. A.B.</span>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Profil Kepaniteraan'
  AND introtext LIKE '%<strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Hadry B., S.H.</span>%';

-- 5. Kepaniteraan Pidana - Ari is no longer acting.

UPDATE #__content
SET introtext = REPLACE(introtext,
        '<span class="roster-role">Plt. Panitera Muda Pidana</span>',
        '<span class="roster-role">Panitera Muda Pidana</span>'),
    `fulltext` = REPLACE(`fulltext`,
        '<span class="roster-role">Plt. Panitera Muda Pidana</span>',
        '<span class="roster-role">Panitera Muda Pidana</span>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Kepaniteraan Pidana'
  AND introtext LIKE '%<span class="roster-role">Plt. Panitera Muda Pidana</span>%';

-- 6. Kepaniteraan Perdata - the card becomes Jhivo Wilanda.

UPDATE #__content
SET introtext = REPLACE(introtext,
        'src="/images/profil/pegawai/kepaniteraan/hadry.jpg" alt="Hadry B., S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Hadry B., S.H.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Perdata</span><span class="roster-role">Panitera</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>197809302011011005</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Tingkat I / III.d</dd></div>',
        'src="/images/profil/pegawai/kepaniteraan/jhivo.png" alt="Jhivo Wilanda, S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Jhivo Wilanda, S.H.</h3><div class="roster-role-row"><span class="roster-role">Panitera Muda Perdata</span><span class="roster-role">Panitera Pengganti</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199604232020121001</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Muda Tk. I / III.b</dd></div>'),
    `fulltext` = REPLACE(`fulltext`,
        'src="/images/profil/pegawai/kepaniteraan/hadry.jpg" alt="Hadry B., S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Hadry B., S.H.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Perdata</span><span class="roster-role">Panitera</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>197809302011011005</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Tingkat I / III.d</dd></div>',
        'src="/images/profil/pegawai/kepaniteraan/jhivo.png" alt="Jhivo Wilanda, S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Jhivo Wilanda, S.H.</h3><div class="roster-role-row"><span class="roster-role">Panitera Muda Perdata</span><span class="roster-role">Panitera Pengganti</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199604232020121001</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Muda Tk. I / III.b</dd></div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Kepaniteraan Perdata'
  AND introtext LIKE '%<h3 class="roster-name">Hadry B., S.H.</h3>%';

-- 7. Kepaniteraan Khusus Perikanan - the card becomes Ari Putra Utama.

UPDATE #__content
SET introtext = REPLACE(introtext,
        'src="/images/profil/pegawai/kepaniteraan/hadry.jpg" alt="Hadry B., S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Hadry B., S.H.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>197809302011011005</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Tingkat I / III.d</dd></div>',
        'src="/images/profil/pegawai/kepaniteraan/ari.webp" alt="Ari Putra Utama, A.Md. A.B." loading="lazy"><span class="roster-degree">D3</span></div><div class="roster-body"><h3 class="roster-name">Ari Putra Utama, A.Md. A.B.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera Pengganti</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199811252022031008</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pengatur Tingkat I / II.d</dd></div>'),
    `fulltext` = REPLACE(`fulltext`,
        'src="/images/profil/pegawai/kepaniteraan/hadry.jpg" alt="Hadry B., S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Hadry B., S.H.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>197809302011011005</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Tingkat I / III.d</dd></div>',
        'src="/images/profil/pegawai/kepaniteraan/ari.webp" alt="Ari Putra Utama, A.Md. A.B." loading="lazy"><span class="roster-degree">D3</span></div><div class="roster-body"><h3 class="roster-name">Ari Putra Utama, A.Md. A.B.</h3><div class="roster-role-row"><span class="roster-role">Plt. Panitera Muda Khusus Perikanan</span><span class="roster-role">Panitera Pengganti</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199811252022031008</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pengatur Tingkat I / II.d</dd></div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE title = 'Kepaniteraan Khusus Perikanan'
  AND introtext LIKE '%<h3 class="roster-name">Hadry B., S.H.</h3>%';
