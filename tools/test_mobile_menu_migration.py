#!/usr/bin/env python3
from __future__ import annotations
import os, subprocess, sys, tempfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]; MYSQL=os.environ.get('MYSQL_BIN',r'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'); SOURCE='pn_natuna_rebuild'; DB=f'pn_natuna_mobile_menu_{os.getpid()}'; MIGRATIONS=[ROOT/'database/migrations/20260722_mobile_navigation_information_architecture.sql',ROOT/'database/migrations/20260723_preserve_mobile_menu_routes.sql']; RUNNER=ROOT/'tools/apply-db-migrations.py'
def mysql(sql,db=None):
 c=[MYSQL,'-h','127.0.0.1','-uroot','--batch','--skip-column-names','--default-character-set=utf8mb4']+([db] if db else [])+['-e',sql]; r=subprocess.run(c,text=True,capture_output=True,encoding='utf-8');
 if r.returncode: raise RuntimeError(r.stderr.strip())
 return r.stdout.strip()
def apply(d,reapply=False):
 c=[sys.executable,str(RUNNER),'--database',DB,'--mysql',MYSQL,'--migrations',str(d)]+(['--reapply'] if reapply else []); r=subprocess.run(c,cwd=ROOT,text=True,capture_output=True)
 if r.returncode: raise RuntimeError(r.stderr.strip())
def snapshot(): return mysql('SELECT id,parent_id,level,title,alias,published,lft,rgt FROM pnn_menu ORDER BY id',DB)
def check():
 expected={'berita-dan-pengumuman':'Berita & Pengumuman','kontak':'Kontak','area-i':'Area I · Manajemen Perubahan','area-vi':'Area VI · Peningkatan Kualitas Pelayanan'}
 for alias,title in expected.items():
  if mysql(f"SELECT title FROM pnn_menu WHERE menutype='mainmenu' AND alias='{alias}'",DB)!=title: raise RuntimeError(f'label failed: {alias}')
 if mysql("SELECT published FROM pnn_menu WHERE menutype='mainmenu' AND alias='penginputan-data-eksekusi'",DB)!='0': raise RuntimeError('internal execution input remains public')
 if mysql("SELECT COUNT(*) FROM pnn_menu WHERE menutype='mainmenu' AND published=0 AND alias IN ('transparansi-akuntabilitas','transparansi-keuangan','transparansi-survei-integritas','transparansi-informasi-publik','perkara-biaya-prosedur','perkara-data-administrasi')",DB)!='6': raise RuntimeError('mobile-only group seeds must remain unpublished')
 if mysql("SELECT parent_id=(SELECT id FROM pnn_menu WHERE menutype='mainmenu' AND alias='transparansi' AND parent_id=1 LIMIT 1) FROM pnn_menu WHERE menutype='mainmenu' AND alias='laporan-keuangan'",DB)!='1': raise RuntimeError('canonical transparency parent was not preserved')
 n=int(mysql('SELECT COUNT(*) FROM pnn_menu',DB)); lo,hi,ul,ur=map(int,mysql('SELECT MIN(lft),MAX(rgt),COUNT(DISTINCT lft),COUNT(DISTINCT rgt) FROM pnn_menu',DB).split('\t'))
 if (lo,hi,ul,ur)!=(1,n*2,n,n): raise RuntimeError('nested set invalid')
def main():
 mysql(f'DROP DATABASE IF EXISTS {DB}; CREATE DATABASE {DB} CHARACTER SET utf8mb4;')
 try:
  for t in ('pnn_menu','pnn_extensions','pnn_project_migrations'): mysql(f'CREATE TABLE {DB}.{t} LIKE {SOURCE}.{t}; INSERT INTO {DB}.{t} SELECT * FROM {SOURCE}.{t};')
  for migration in MIGRATIONS: mysql(f"DELETE FROM {DB}.pnn_project_migrations WHERE name='{migration.name}'")
  with tempfile.TemporaryDirectory() as td:
   d=Path(td)
   for migration in MIGRATIONS: (d/migration.name).write_text(migration.read_text(encoding='utf-8'),encoding='utf-8')
   apply(d); check(); stable=snapshot()
   for _ in range(3): apply(d,True); check(); assert snapshot()==stable
  print('mobile menu migration contract: ok')
 finally: mysql(f'DROP DATABASE IF EXISTS {DB}')
if __name__=='__main__':
 try: main()
 except (OSError,RuntimeError,AssertionError) as e: print('error:',e,file=sys.stderr); raise SystemExit(1)
