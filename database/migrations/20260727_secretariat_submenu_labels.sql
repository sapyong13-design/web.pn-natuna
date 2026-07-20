-- Publish and alphabetize Kesekretariatan submenu labels using complete unit names.

SET @secretariat_menu_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND path='profil-pengadilan/profil-kesekretariatan' LIMIT 1);

UPDATE #__content SET publish_up='2026-01-01 00:00:00' WHERE alias IN ('subbagian-kepegawaian-ortala','subbagian-ptip','subbagian-umum-keuangan');
UPDATE #__menu SET title='Kepegawaian, Organisasi, dan Tata Laksana',lft=1 WHERE menutype='mainmenu' AND parent_id=@secretariat_menu_id AND alias='subbagian-kepegawaian-ortala';
UPDATE #__menu SET title='Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP)',lft=2 WHERE menutype='mainmenu' AND parent_id=@secretariat_menu_id AND alias='subbagian-ptip';
UPDATE #__menu SET title='Umum dan Keuangan',lft=3 WHERE menutype='mainmenu' AND parent_id=@secretariat_menu_id AND alias='subbagian-umum-keuangan';

CREATE TEMPORARY TABLE secretariat_label_nav_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO secretariat_label_nav_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL
 SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN secretariat_label_nav_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE secretariat_label_nav_bounds;
