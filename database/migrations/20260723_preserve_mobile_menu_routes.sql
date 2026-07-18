-- Restore canonical Joomla parents after mobile IA grouping proved route-sensitive.
-- Mobile-only visual grouping is rendered by template JavaScript instead.
SET @transparency_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND alias='transparansi' AND parent_id=1 LIMIT 1);
SET @case_id := (SELECT id FROM #__menu WHERE menutype='mainmenu' AND alias='informasi-perkara' AND parent_id=1 LIMIT 1);
UPDATE #__menu SET parent_id=@transparency_id WHERE menutype='mainmenu' AND alias IN ('ringkasan-lkjip','laporan-tahunan','sakip','laporan-realisasi-anggaran','laporan-keuangan','lhkpn','laporan-skm','laporan-spak','laporan-survei-harian','e-brosur','peraturan-kebijakan','lelang-barang-jasa','laporan-pelayanan-informasi-publik');
UPDATE #__menu SET parent_id=@case_id WHERE menutype='mainmenu' AND alias IN ('biaya-perkara','prosedur-pengajuan-perkara','prosedur-eksekusi','data-eksekusi','penginputan-data-eksekusi','panggilan-umum');
UPDATE #__menu SET published=0 WHERE menutype='mainmenu' AND alias IN ('transparansi-akuntabilitas','transparansi-keuangan','transparansi-survei-integritas','transparansi-informasi-publik','perkara-biaya-prosedur','perkara-data-administrasi');
CREATE TEMPORARY TABLE mobile_route_bounds (id INT PRIMARY KEY,lft INT NOT NULL,rgt INT NOT NULL,level INT NOT NULL);
INSERT INTO mobile_route_bounds
WITH RECURSIVE tree AS (
 SELECT id,parent_id,CAST(CONCAT(LPAD(lft,10,'0'),':',LPAD(id,10,'0')) AS CHAR(30000)) sort_path FROM #__menu WHERE id=1
 UNION ALL SELECT c.id,c.parent_id,CONCAT(p.sort_path,'/',LPAD(c.lft,10,'0'),':',LPAD(c.id,10,'0')) FROM #__menu c JOIN tree p ON c.parent_id=p.id
), events AS (
 SELECT id,CONCAT(sort_path,'/0') event_path,'o' kind FROM tree UNION ALL SELECT id,CONCAT(sort_path,'/z'),'c' FROM tree
), numbered AS (SELECT id,kind,ROW_NUMBER() OVER (ORDER BY event_path) boundary FROM events)
SELECT t.id,MAX(IF(kind='o',boundary,NULL)),MAX(IF(kind='c',boundary,NULL)),LENGTH(t.sort_path)-LENGTH(REPLACE(t.sort_path,'/','')) FROM tree t JOIN numbered n ON n.id=t.id GROUP BY t.id,t.sort_path;
UPDATE #__menu m JOIN mobile_route_bounds b ON b.id=m.id SET m.lft=b.lft,m.rgt=b.rgt,m.level=b.level;
DROP TEMPORARY TABLE mobile_route_bounds;
