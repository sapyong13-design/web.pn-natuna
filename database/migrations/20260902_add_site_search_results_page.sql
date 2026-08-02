-- Halaman hasil pencarian situs.
--
-- Kotak pencarian ada di setiap halaman, tetapi formulirnya menunjuk
-- `/component/search/` - komponen `com_search` yang sudah dihapus Joomla sejak versi 4.
-- Warga yang mengetik "posbakum" mendarat di 404. Penggantinya, `com_finder`, aktif dan
-- indeksnya kini terisi, tetapi tanpa item menu situs router jatuh ke Beranda dan
-- keluaran komponennya dibuang templat - halaman beranda tidak memuat
-- `jdoc:include type="component"`.
--
-- Item ini ditaruh di menu `hidden` supaya tidak muncul di navigasi utama; ia hanya
-- menyediakan rute `/cari` yang dituju formulir.
SET @finder_component_id := (SELECT extension_id FROM #__extensions WHERE element='com_finder' AND type='component' LIMIT 1);
CREATE TEMPORARY TABLE search_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=1));
INSERT INTO search_dependency_check VALUES ((@finder_component_id IS NOT NULL));
DROP TEMPORARY TABLE search_dependency_check;

INSERT INTO #__menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id)
SELECT 'hidden','Cari Informasi','cari','pn-natuna-production-menu','cari','index.php?option=com_finder&view=search','component',1,1,1,@finder_component_id,NULL,NULL,0,1,'',0,'{"show_page_heading":"1","page_title":"Cari Informasi","show_search_form":"1","show_advanced":"0","show_date_filters":"0","expand_advanced":"0","show_feed_link":"0","robots":"noindex, follow"}',0,1,0,'*',0
WHERE NOT EXISTS (SELECT 1 FROM #__menu WHERE menutype='hidden' AND alias='cari' AND client_id=0);

UPDATE #__menu
SET title='Cari Informasi',
    published=1,
    parent_id=1,
    level=1,
    component_id=@finder_component_id,
    path='cari',
    link='index.php?option=com_finder&view=search',
    type='component',
    access=1,
    language='*',
    home=0,
    params='{"show_page_heading":"1","page_title":"Cari Informasi","show_search_form":"1","show_advanced":"0","show_date_filters":"0","expand_advanced":"0","show_feed_link":"0","robots":"noindex, follow"}'
WHERE menutype='hidden' AND alias='cari' AND client_id=0;

-- Nested set dibangun ulang dengan pola yang sama seperti migrasi menu sebelumnya:
-- urutan dihitung dari pohon parent_id, lalu lft/rgt/level ditulis ulang seluruhnya.
-- Aman diputar berkali-kali karena hasilnya hanya bergantung pada isi tabel.
CREATE TEMPORARY TABLE search_nav_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO search_nav_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL
 SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN search_nav_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE search_nav_bounds;
