-- Add canonical Kesekretariatan unit articles and nested menu items.
-- Replay-safe: stable aliases preserve existing content and routes.

SET @content_component_id := (SELECT extension_id FROM #__extensions WHERE element='com_content' AND type='component' ORDER BY extension_id LIMIT 1);
SET @secretariat_menu_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND path='profil-pengadilan/profil-kesekretariatan' LIMIT 1);
SET @profile_category_id := (SELECT catid FROM #__content WHERE alias='profil-kesekretariatan' LIMIT 1);

CREATE TEMPORARY TABLE secretariat_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=3));
INSERT INTO secretariat_dependency_check VALUES ((@content_component_id IS NOT NULL)+(@secretariat_menu_id IS NOT NULL)+(@profile_category_id IS NOT NULL));
DROP TEMPORARY TABLE secretariat_dependency_check;

INSERT INTO #__content (title,alias,introtext,`fulltext`,state,catid,created,created_by,modified,publish_up,images,urls,attribs,version,ordering,metadesc,access,hits,metadata,featured,language,note)
SELECT seed.title,seed.alias,seed.introtext,'',1,@profile_category_id,NOW(),0,NOW(),NOW(),'{}','{}','{}',1,0,'',1,0,'{}',0,'*','secretariat-unit'
FROM (
 SELECT 'Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan' title,'subbagian-ptip' alias,'<p class="roster-lead">Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP) mendukung perencanaan program dan anggaran, pengelolaan teknologi informasi, serta penyusunan laporan Pengadilan Negeri Natuna.</p>' introtext UNION ALL
 SELECT 'Subbagian Kepegawaian, Organisasi, dan Tata Laksana','subbagian-kepegawaian-ortala','<p class="roster-lead">Subbagian Kepegawaian, Organisasi, dan Tata Laksana mengelola administrasi kepegawaian, organisasi, tata laksana, dan pengembangan aparatur Pengadilan Negeri Natuna.</p>' UNION ALL
 SELECT 'Subbagian Umum dan Keuangan','subbagian-umum-keuangan','<p class="roster-lead">Subbagian Umum dan Keuangan mendukung tata usaha, perlengkapan, rumah tangga, persuratan, serta pengelolaan keuangan Pengadilan Negeri Natuna.</p>'
) seed
WHERE NOT EXISTS (SELECT 1 FROM #__content c WHERE c.alias=seed.alias);

UPDATE #__content SET title='Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan',state=1,catid=@profile_category_id WHERE alias='subbagian-ptip';
UPDATE #__content SET title='Subbagian Kepegawaian, Organisasi, dan Tata Laksana',state=1,catid=@profile_category_id WHERE alias='subbagian-kepegawaian-ortala';
UPDATE #__content SET title='Subbagian Umum dan Keuangan',state=1,catid=@profile_category_id WHERE alias='subbagian-umum-keuangan';

INSERT INTO #__menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id)
SELECT 'mainmenu',seed.title,seed.alias,'pn-natuna-production-menu',CONCAT(parent.path,'/',seed.alias),CONCAT('index.php?option=com_content&view=article&id=',content.id),'component',1,@secretariat_menu_id,3,@content_component_id,NULL,NULL,0,1,'',0,'{"show_hits":"0","show_tags":"0","show_intro":"1","show_title":"1","show_author":"0","show_publish_date":"0"}',seed.sort_lft,seed.sort_lft+1,0,'*',0
FROM (
 SELECT 'PTIP' title,'subbagian-ptip' alias,1 sort_lft UNION ALL
 SELECT 'Kepegawaian','subbagian-kepegawaian-ortala',2 UNION ALL
 SELECT 'Umum dan Keuangan','subbagian-umum-keuangan',3
) seed
JOIN #__menu parent ON parent.id=@secretariat_menu_id
JOIN #__content content ON content.alias=seed.alias
WHERE NOT EXISTS (SELECT 1 FROM #__menu m WHERE m.menutype='mainmenu' AND m.parent_id=@secretariat_menu_id AND m.alias=seed.alias);

UPDATE #__menu SET title='PTIP',published=1,parent_id=@secretariat_menu_id,level=3,path='profil-pengadilan/profil-kesekretariatan/subbagian-ptip',lft=1 WHERE menutype='mainmenu' AND alias='subbagian-ptip';
UPDATE #__menu SET title='Kepegawaian',published=1,parent_id=@secretariat_menu_id,level=3,path='profil-pengadilan/profil-kesekretariatan/subbagian-kepegawaian-ortala',lft=2 WHERE menutype='mainmenu' AND alias='subbagian-kepegawaian-ortala';
UPDATE #__menu SET title='Umum dan Keuangan',published=1,parent_id=@secretariat_menu_id,level=3,path='profil-pengadilan/profil-kesekretariatan/subbagian-umum-keuangan',lft=3 WHERE menutype='mainmenu' AND alias='subbagian-umum-keuangan';

CREATE TEMPORARY TABLE secretariat_nav_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO secretariat_nav_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL
 SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN secretariat_nav_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE secretariat_nav_bounds;
