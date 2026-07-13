#!/usr/bin/env python3
"""Focused integration contract for public AMPUH mainmenu migration."""
from __future__ import annotations
import os
from pathlib import Path
import subprocess
import sys
import tempfile
ROOT=Path(__file__).resolve().parents[1]; MYSQL=os.environ.get('MYSQL_BIN',r'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'); SOURCE='pn_natuna_rebuild'; DATABASE=f'pn_natuna_ampuh_menu_test_{os.getpid()}'; MIGRATION=ROOT/'database/migrations/20260717_ampuh_mainmenu.sql'; RUNNER=ROOT/'tools'/'apply-db-migrations.py'
def mysql(sql,database=None):
 c=[MYSQL,'-uroot','--batch','--skip-column-names','--default-character-set=utf8mb4']+([database] if database else []); r=subprocess.run(c+['-e',sql],text=True,encoding='utf-8',capture_output=True)
 if r.returncode: raise RuntimeError(r.stderr.strip())
 return r.stdout.strip()
def apply(d,reapply=False):
 r=subprocess.run([sys.executable,str(RUNNER),'--database',DATABASE,'--mysql',MYSQL,'--migrations',str(d)]+(['--reapply'] if reapply else []),cwd=ROOT,text=True,encoding='utf-8',capture_output=True)
 if r.returncode: raise RuntimeError(r.stderr.strip())
def snap(): return mysql("SELECT GROUP_CONCAT(CONCAT(id,':',parent_id,':',lft,':',rgt) ORDER BY lft SEPARATOR '|') FROM pnn_menu",DATABASE)
def check(cid,hidden,stable=None):
 p=mysql("SELECT id,title,alias,path,published,access,language,client_id,link FROM pnn_menu WHERE menutype='mainmenu' AND parent_id=1 AND alias='ampuh'",DATABASE).split('\t')
 if p[:8]!=[cid,'AMPUH','ampuh','ampuh','1','1','id-ID','0'] or not p[8].startswith('index.php?option=com_content&view=article&id='): raise RuntimeError('canonical route or ID failed')
 if mysql("SELECT id,title,alias,path,parent_id,language,client_id FROM pnn_menu WHERE menutype='hidden' AND alias='ampuh' AND parent_id=1",DATABASE)!=hidden: raise RuntimeError('hidden row changed')
 if mysql("SELECT GROUP_CONCAT(CONCAT(id,':',title) ORDER BY lft SEPARATOR ' > ') FROM pnn_menu WHERE menutype='mainmenu' AND parent_id=1 AND id IN (108,109,"+cid+")",DATABASE)!=f'108:Transparansi > {cid}:AMPUH > 109:Hubungi Kami': raise RuntimeError('navbar order failed')
 if mysql("SELECT COUNT(*) FROM pnn_menu WHERE parent_id="+cid+" AND alias='ampuh-child'",DATABASE)!='1': raise RuntimeError('canonical child lost')
 if mysql("SELECT COUNT(*) FROM pnn_modules_menu WHERE menuid<>0 AND ABS(menuid) NOT IN (SELECT id FROM pnn_menu)",DATABASE)!='0': raise RuntimeError('stale module mapping')
 lo,hi,n,ul,ur=map(int,mysql('SELECT MIN(lft),MAX(rgt),COUNT(*),COUNT(DISTINCT lft),COUNT(DISTINCT rgt) FROM pnn_menu',DATABASE).split('\t'))
 if (lo,hi,ul,ur)!=(1,n*2,n,n): raise RuntimeError('global contiguous bounds failed')
 now=snap()
 if stable is not None and now!=stable: raise RuntimeError('replay bounds drifted')
 return now
def main():
 mysql(f'DROP DATABASE IF EXISTS {DATABASE}; CREATE DATABASE {DATABASE} CHARACTER SET utf8mb4;')
 try:
  mysql(f"CREATE TABLE {DATABASE}.pnn_content LIKE {SOURCE}.pnn_content; INSERT INTO {DATABASE}.pnn_content SELECT * FROM {SOURCE}.pnn_content; CREATE TABLE {DATABASE}.pnn_menu LIKE {SOURCE}.pnn_menu; INSERT INTO {DATABASE}.pnn_menu SELECT * FROM {SOURCE}.pnn_menu; CREATE TABLE {DATABASE}.pnn_extensions LIKE {SOURCE}.pnn_extensions; INSERT INTO {DATABASE}.pnn_extensions SELECT * FROM {SOURCE}.pnn_extensions; CREATE TABLE {DATABASE}.pnn_modules_menu LIKE {SOURCE}.pnn_modules_menu; INSERT INTO {DATABASE}.pnn_modules_menu SELECT * FROM {SOURCE}.pnn_modules_menu; DELETE FROM {DATABASE}.pnn_menu WHERE menutype='mainmenu' AND alias='ampuh'; SET @e=(SELECT MAX(rgt) FROM {DATABASE}.pnn_menu); UPDATE {DATABASE}.pnn_menu SET rgt=rgt+8 WHERE rgt>@e; INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Legacy AMPUH','ampuh','','ampuh','index.php?option=com_content&view=article&id=1','component',0,1,1,extension_id,NULL,NULL,0,1,'',0,'{{}}',@e+1,@e+4,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; SET @c=LAST_INSERT_ID(); INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','AMPUH child','ampuh-child','','ampuh/child','index.php?option=com_content&view=article&id=1','component',1,@c,2,extension_id,NULL,NULL,0,1,'',0,'{{}}',@e+2,@e+3,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Duplicate AMPUH','ampuh','','ampuh','index.php?option=com_content&view=article&id=1','component',1,1,1,extension_id,NULL,NULL,0,1,'',0,'{{}}',@e+5,@e+8,0,'id-ID',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; SET @d=LAST_INSERT_ID(); INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Duplicate child','duplicate-child','','ampuh/duplicate-child','index.php?option=com_content&view=article&id=1','component',1,@d,2,extension_id,NULL,NULL,0,1,'',0,'{{}}',@e+6,@e+7,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; INSERT INTO {DATABASE}.pnn_modules_menu (moduleid,menuid) SELECT moduleid,@d FROM {DATABASE}.pnn_modules_menu LIMIT 1; INSERT INTO {DATABASE}.pnn_modules_menu (moduleid,menuid) SELECT moduleid,-@d FROM {DATABASE}.pnn_modules_menu LIMIT 1;")
  cid=mysql("SELECT id FROM pnn_menu WHERE title='Legacy AMPUH'",DATABASE); hidden=mysql("SELECT id,title,alias,path,parent_id,language,client_id FROM pnn_menu WHERE menutype='hidden' AND alias='ampuh' AND parent_id=1",DATABASE)
  with tempfile.TemporaryDirectory() as x:
   d=Path(x); (d/MIGRATION.name).write_text(MIGRATION.read_text(encoding='utf-8'),encoding='utf-8'); apply(d); s=check(cid,hidden)
   for _ in range(3): apply(d,True); check(cid,hidden,s)
  print('AMPUH mainmenu migration contract: ok')
 finally: mysql(f'DROP DATABASE IF EXISTS {DATABASE};')
if __name__=='__main__':
 try: main()
 except (OSError,RuntimeError) as e: print('error:',e,file=sys.stderr); raise SystemExit(1)
