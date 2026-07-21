-- Add Joko Ciptanto's welcome page as the first child of Tentang Pengadilan.
-- Official 2026 sources identify him as Wakil Ketua, so the public title must not imply Ketua.
SET @content_component_id := (SELECT component_id FROM #__menu WHERE menutype='mainmenu' AND alias='sejarah-pengadilan' LIMIT 1);
SET @profile_menu_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND alias='profil-pengadilan' AND parent_id=1 LIMIT 1);
SET @profile_category_id := (SELECT catid FROM #__content WHERE alias='profil-pengadilan' LIMIT 1);
CREATE TEMPORARY TABLE welcome_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=3));
INSERT INTO welcome_dependency_check VALUES ((@content_component_id IS NOT NULL)+(@profile_menu_id IS NOT NULL)+(@profile_category_id IS NOT NULL));
DROP TEMPORARY TABLE welcome_dependency_check;

SET @welcome_content := '<article class="leadership-welcome"><header class="leadership-welcome__hero"><div><p class="leadership-welcome__eyebrow">Tentang Pengadilan</p><h2>Sambutan Wakil Ketua</h2><p>Komitmen Pengadilan Negeri Natuna untuk menghadirkan informasi dan layanan peradilan yang terbuka, modern, dan mudah dijangkau.</p></div><figure><img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg" alt="Joko Ciptanto, S.H., M.H., Wakil Ketua Pengadilan Negeri Natuna" width="600" height="800" loading="eager" decoding="async"><figcaption><strong>Joko Ciptanto, S.H., M.H.</strong><span>Wakil Ketua Pengadilan Negeri Natuna</span></figcaption></figure></header><section class="leadership-welcome__message"><p class="leadership-welcome__greeting">Assalamu&rsquo;alaikum warahmatullahi wabarakatuh.</p><p>Puji syukur ke hadirat Tuhan Yang Maha Esa atas terselenggaranya situs resmi Pengadilan Negeri Natuna. Situs ini menjadi media informasi bagi para pencari keadilan di wilayah hukum Pengadilan Negeri Natuna dan masyarakat Indonesia secara umum.</p><p>Pengembangan situs ini merupakan bagian dari pelaksanaan keterbukaan informasi di pengadilan sebagaimana diamanatkan dalam Keputusan Ketua Mahkamah Agung Republik Indonesia Nomor 2-144/KMA/SK/VIII/2022. Melalui situs ini, masyarakat dapat memperoleh informasi mengenai profil, layanan, program, kegiatan, informasi hukum, berita, dan pengumuman resmi Pengadilan Negeri Natuna.</p><p>Kami menyadari bahwa penyajian informasi dan layanan digital harus terus diperbaiki. Kritik dan saran yang membangun sangat berarti untuk meningkatkan kualitas situs serta pelayanan kepada masyarakat.</p><p>Semoga situs ini mendukung keterbukaan, akuntabilitas, dan modernisasi peradilan, sekaligus memudahkan masyarakat memperoleh layanan dan informasi yang dibutuhkan.</p><p class="leadership-welcome__closing">Wassalamu&rsquo;alaikum warahmatullahi wabarakatuh.</p><footer><span>Pengadilan Negeri Natuna Kelas II</span><strong>Joko Ciptanto, S.H., M.H.</strong><small>Wakil Ketua</small></footer></section></article>';

INSERT INTO #__content (title,alias,introtext,`fulltext`,state,catid,created,created_by,modified,publish_up,images,urls,attribs,version,ordering,metadesc,access,hits,metadata,featured,language,note)
SELECT 'Sambutan Wakil Ketua','kata-sambutan',@welcome_content,'',1,@profile_category_id,UTC_TIMESTAMP(),0,UTC_TIMESTAMP(),'2000-01-01 00:00:00','{"image_intro":"images/profil/pegawai/hakim/joko-ciptanto.jpg","image_intro_alt":"Joko Ciptanto, S.H., M.H."}','{}','{}',1,0,'Sambutan Wakil Ketua Pengadilan Negeri Natuna mengenai keterbukaan informasi dan layanan peradilan.',1,0,'{}',0,'*','leadership-welcome'
WHERE NOT EXISTS (SELECT 1 FROM #__content WHERE alias='kata-sambutan');
UPDATE #__content SET title='Sambutan Wakil Ketua',introtext=@welcome_content,state=1,catid=@profile_category_id,modified=UTC_TIMESTAMP(),publish_up='2000-01-01 00:00:00',publish_down=NULL,metadesc='Sambutan Wakil Ketua Pengadilan Negeri Natuna mengenai keterbukaan informasi dan layanan peradilan.' WHERE alias='kata-sambutan';
SET @welcome_content_id := (SELECT id FROM #__content WHERE alias='kata-sambutan' ORDER BY id LIMIT 1);

INSERT INTO #__menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id)
SELECT 'mainmenu','Sambutan Wakil Ketua','kata-sambutan','pn-natuna-production-menu','profil-pengadilan/kata-sambutan',CONCAT('index.php?option=com_content&view=article&id=',@welcome_content_id),'component',1,@profile_menu_id,2,@content_component_id,NULL,NULL,0,1,'',0,'{"show_hits":"0","show_tags":"0","show_intro":"1","show_title":"1","show_author":"0","show_publish_date":"0"}',0,1,0,'*',0
WHERE NOT EXISTS (SELECT 1 FROM #__menu WHERE menutype='mainmenu' AND alias='kata-sambutan');
UPDATE #__menu SET title='Sambutan Wakil Ketua',published=1,parent_id=@profile_menu_id,level=2,component_id=@content_component_id,path='profil-pengadilan/kata-sambutan',link=CONCAT('index.php?option=com_content&view=article&id=',@welcome_content_id),lft=0 WHERE menutype='mainmenu' AND alias='kata-sambutan';

CREATE TEMPORARY TABLE welcome_nav_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO welcome_nav_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL
 SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN welcome_nav_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE welcome_nav_bounds;
