-- Mobile navigation information architecture: descriptive labels, grouped long menus, and canonical public items.
-- Replay-safe. Existing article menu IDs and links remain canonical.

SET @content_component_id := (SELECT extension_id FROM #__extensions WHERE element='com_content' AND type='component' ORDER BY extension_id LIMIT 1);
SET @transparency_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND alias='transparansi' AND parent_id=1 LIMIT 1);
SET @case_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND alias='informasi-perkara' AND parent_id=1 LIMIT 1);

CREATE TEMPORARY TABLE mobile_nav_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=3));
INSERT INTO mobile_nav_dependency_check VALUES ((@content_component_id IS NOT NULL)+(@transparency_id IS NOT NULL)+(@case_id IS NOT NULL));
DROP TEMPORARY TABLE mobile_nav_dependency_check;

UPDATE #__menu SET title='Berita & Pengumuman' WHERE menutype='mainmenu' AND alias='berita-dan-pengumuman' AND parent_id=1;
UPDATE #__menu SET title='Kontak' WHERE menutype='mainmenu' AND alias='kontak' AND parent_id=1;
UPDATE #__menu SET published=0 WHERE menutype='mainmenu' AND alias='penginputan-data-eksekusi';
UPDATE #__menu SET title='Area I · Manajemen Perubahan' WHERE menutype='mainmenu' AND alias='area-i' AND parent_id=(SELECT id FROM (SELECT id FROM #__menu WHERE alias='zona-integritas' AND parent_id=1 LIMIT 1) x);
UPDATE #__menu SET title='Area II · Penataan Tata Laksana' WHERE menutype='mainmenu' AND alias='area-ii';
UPDATE #__menu SET title='Area III · Penataan Sistem SDM' WHERE menutype='mainmenu' AND alias='area-iii';
UPDATE #__menu SET title='Area IV · Penguatan Akuntabilitas' WHERE menutype='mainmenu' AND alias='area-iv';
UPDATE #__menu SET title='Area V · Penguatan Pengawasan' WHERE menutype='mainmenu' AND alias='area-v';
UPDATE #__menu SET title='Area VI · Peningkatan Kualitas Pelayanan' WHERE menutype='mainmenu' AND alias='area-vi';

-- Upsert non-navigating group headings by stable alias.
INSERT INTO #__menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id)
SELECT 'mainmenu', seed.title, seed.alias, '', CONCAT(parent.path,'/',seed.alias), '#', 'separator', 1, seed.parent_id, 2, 0, NULL,NULL,0,1,'',0,'{}',seed.sort_lft,seed.sort_lft+1,0,'id-ID',0
FROM (
 SELECT @transparency_id parent_id,'Akuntabilitas Kinerja' title,'transparansi-akuntabilitas' alias,283 sort_lft UNION ALL
 SELECT @transparency_id,'Keuangan','transparansi-keuangan',289 UNION ALL
 SELECT @transparency_id,'Survei & Integritas','transparansi-survei-integritas',297 UNION ALL
 SELECT @transparency_id,'Informasi Publik','transparansi-informasi-publik',303 UNION ALL
 SELECT @case_id,'Biaya & Prosedur','perkara-biaya-prosedur',243 UNION ALL
 SELECT @case_id,'Data & Administrasi','perkara-data-administrasi',249
) seed
JOIN #__menu parent ON parent.id=seed.parent_id
WHERE NOT EXISTS (SELECT 1 FROM #__menu m WHERE m.menutype='mainmenu' AND m.alias=seed.alias);

UPDATE #__menu SET title='Akuntabilitas Kinerja', published=1, type='separator', link='#', parent_id=@transparency_id WHERE alias='transparansi-akuntabilitas' AND menutype='mainmenu';
UPDATE #__menu SET title='Keuangan', published=1, type='separator', link='#', parent_id=@transparency_id WHERE alias='transparansi-keuangan' AND menutype='mainmenu';
UPDATE #__menu SET title='Survei & Integritas', published=1, type='separator', link='#', parent_id=@transparency_id WHERE alias='transparansi-survei-integritas' AND menutype='mainmenu';
UPDATE #__menu SET title='Informasi Publik', published=1, type='separator', link='#', parent_id=@transparency_id WHERE alias='transparansi-informasi-publik' AND menutype='mainmenu';
UPDATE #__menu SET title='Biaya & Prosedur', published=1, type='separator', link='#', parent_id=@case_id WHERE alias='perkara-biaya-prosedur' AND menutype='mainmenu';
UPDATE #__menu SET title='Data & Administrasi', published=1, type='separator', link='#', parent_id=@case_id WHERE alias='perkara-data-administrasi' AND menutype='mainmenu';

SET @ta := (SELECT id FROM #__menu WHERE alias='transparansi-akuntabilitas' AND menutype='mainmenu' LIMIT 1);
SET @tk := (SELECT id FROM #__menu WHERE alias='transparansi-keuangan' AND menutype='mainmenu' LIMIT 1);
SET @ts := (SELECT id FROM #__menu WHERE alias='transparansi-survei-integritas' AND menutype='mainmenu' LIMIT 1);
SET @ti := (SELECT id FROM #__menu WHERE alias='transparansi-informasi-publik' AND menutype='mainmenu' LIMIT 1);
SET @pb := (SELECT id FROM #__menu WHERE alias='perkara-biaya-prosedur' AND menutype='mainmenu' LIMIT 1);
SET @pd := (SELECT id FROM #__menu WHERE alias='perkara-data-administrasi' AND menutype='mainmenu' LIMIT 1);

UPDATE #__menu SET parent_id=@ta WHERE menutype='mainmenu' AND alias IN ('ringkasan-lkjip','laporan-tahunan','sakip');
UPDATE #__menu SET parent_id=@tk WHERE menutype='mainmenu' AND alias IN ('laporan-realisasi-anggaran','laporan-keuangan','lhkpn','lelang-barang-jasa');
UPDATE #__menu SET parent_id=@ts WHERE menutype='mainmenu' AND alias IN ('laporan-skm','laporan-spak','laporan-survei-harian');
UPDATE #__menu SET parent_id=@ti WHERE menutype='mainmenu' AND alias IN ('e-brosur','peraturan-kebijakan','laporan-pelayanan-informasi-publik');
UPDATE #__menu SET parent_id=@pb WHERE menutype='mainmenu' AND alias IN ('biaya-perkara','prosedur-pengajuan-perkara','prosedur-eksekusi');
UPDATE #__menu SET parent_id=@pd WHERE menutype='mainmenu' AND alias IN ('data-eksekusi','penginputan-data-eksekusi','panggilan-umum');

-- Stable sort points inside each new group.
UPDATE #__menu SET lft=CASE alias WHEN 'ringkasan-lkjip' THEN 1 WHEN 'laporan-tahunan' THEN 2 WHEN 'sakip' THEN 3 ELSE lft END WHERE parent_id=@ta;
UPDATE #__menu SET lft=CASE alias WHEN 'laporan-realisasi-anggaran' THEN 1 WHEN 'laporan-keuangan' THEN 2 WHEN 'lhkpn' THEN 3 WHEN 'lelang-barang-jasa' THEN 4 ELSE lft END WHERE parent_id=@tk;
UPDATE #__menu SET lft=CASE alias WHEN 'laporan-skm' THEN 1 WHEN 'laporan-spak' THEN 2 WHEN 'laporan-survei-harian' THEN 3 ELSE lft END WHERE parent_id=@ts;
UPDATE #__menu SET lft=CASE alias WHEN 'e-brosur' THEN 1 WHEN 'peraturan-kebijakan' THEN 2 WHEN 'laporan-pelayanan-informasi-publik' THEN 3 ELSE lft END WHERE parent_id=@ti;
UPDATE #__menu SET lft=CASE alias WHEN 'biaya-perkara' THEN 1 WHEN 'prosedur-pengajuan-perkara' THEN 2 WHEN 'prosedur-eksekusi' THEN 3 ELSE lft END WHERE parent_id=@pb;
UPDATE #__menu SET lft=CASE alias WHEN 'data-eksekusi' THEN 1 WHEN 'panggilan-umum' THEN 2 WHEN 'penginputan-data-eksekusi' THEN 3 ELSE lft END WHERE parent_id=@pd;

-- Rebuild the complete Joomla nested set from parent relationships and stable sibling sort points.
CREATE TEMPORARY TABLE mobile_nav_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO mobile_nav_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL
 SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN mobile_nav_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE mobile_nav_bounds;
