#!/usr/bin/env python3
"""Focused integration contract for public AMPUH mainmenu migration."""
from __future__ import annotations
import os
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[1]
MYSQL = os.environ.get("MYSQL_BIN", r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
SOURCE = "pn_natuna_rebuild"
DATABASE = f"pn_natuna_ampuh_menu_test_{os.getpid()}"
MIGRATION = ROOT / "database" / "migrations" / "20260717_ampuh_mainmenu.sql"
RUNNER = ROOT / "tools" / "apply-db-migrations.py"

def mysql(sql: str, database: str | None = None) -> str:
    command = [MYSQL, "-uroot", "--batch", "--skip-column-names", "--default-character-set=utf8mb4"]
    if database: command.append(database)
    result = subprocess.run(command + ["-e", sql], text=True, encoding="utf-8", capture_output=True)
    if result.returncode: raise RuntimeError(result.stderr.strip())
    return result.stdout.strip()

def apply(directory: Path, reapply: bool = False) -> None:
    command = [sys.executable, str(RUNNER), "--database", DATABASE, "--mysql", MYSQL, "--migrations", str(directory)]
    if reapply: command.append("--reapply")
    result = subprocess.run(command, cwd=ROOT, text=True, encoding="utf-8", capture_output=True)
    if result.returncode: raise RuntimeError(result.stderr.strip())

def snapshot() -> str:
    return mysql("SELECT GROUP_CONCAT(CONCAT(id,':',parent_id,':',lft,':',rgt) ORDER BY lft SEPARATOR '|') FROM pnn_menu;", DATABASE)

def assert_contract(canonical_id: str, hidden: str, expected: str | None) -> str:
    public = mysql("SELECT id,title,alias,path,published,access,language,client_id,link FROM pnn_menu WHERE menutype='mainmenu' AND parent_id=1 AND alias='ampuh';", DATABASE).split("\t")
    if public[:8] != [canonical_id, "AMPUH", "ampuh", "ampuh", "1", "1", "id-ID", "0"] or not public[8].startswith("index.php?option=com_content&view=article&id="):
        raise RuntimeError(f"canonical public route/stable ID failed: {public}")
    if mysql("SELECT id,title,alias,path,parent_id,language,client_id FROM pnn_menu WHERE menutype='hidden' AND alias='ampuh' AND parent_id=1;", DATABASE) != hidden:
        raise RuntimeError("hidden row fields changed")
    order = mysql("SELECT GROUP_CONCAT(CONCAT(id,':',title) ORDER BY lft SEPARATOR ' > ') FROM pnn_menu WHERE menutype='mainmenu' AND parent_id=1 AND id IN (108,109," + canonical_id + ");", DATABASE)
    if order != f"108:Transparansi > {canonical_id}:AMPUH > 109:Hubungi Kami": raise RuntimeError(f"wrong navbar order: {order}")
    if mysql("SELECT COUNT(*) FROM pnn_menu WHERE parent_id=" + canonical_id + " AND alias='ampuh-child';", DATABASE) != "1": raise RuntimeError("canonical child lost")
    if mysql("SELECT COUNT(*) FROM pnn_menu WHERE alias='duplicate-child';", DATABASE) != "0": raise RuntimeError("duplicate subtree not removed")
    invalid = mysql("SELECT COUNT(*) FROM pnn_menu m LEFT JOIN pnn_menu p ON p.id=m.parent_id WHERE m.id<>1 AND (p.id IS NULL OR p.lft>=m.lft OR p.rgt<=m.rgt);", DATABASE)
    if invalid != "0": raise RuntimeError(f"invalid global containment: {invalid}")
    bounds = mysql("SELECT MIN(lft),MAX(rgt),COUNT(*),COUNT(DISTINCT lft),COUNT(DISTINCT rgt) FROM pnn_menu;", DATABASE)
    low, high, count, ul, ur = map(int, bounds.split("\t"))
    if (low, high, ul, ur) != (1, count * 2, count, count): raise RuntimeError(f"global bounds not contiguous: {bounds}")
    current = snapshot()
    if expected is not None and current != expected: raise RuntimeError("global bounds drifted")
    return current

def main() -> int:
    mysql(f"DROP DATABASE IF EXISTS {DATABASE}; CREATE DATABASE {DATABASE} CHARACTER SET utf8mb4;")
    try:
        mysql(
            f"CREATE TABLE {DATABASE}.pnn_content LIKE {SOURCE}.pnn_content; INSERT INTO {DATABASE}.pnn_content SELECT * FROM {SOURCE}.pnn_content;"
            f"CREATE TABLE {DATABASE}.pnn_menu LIKE {SOURCE}.pnn_menu; INSERT INTO {DATABASE}.pnn_menu SELECT * FROM {SOURCE}.pnn_menu;"
            f"CREATE TABLE {DATABASE}.pnn_extensions LIKE {SOURCE}.pnn_extensions; INSERT INTO {DATABASE}.pnn_extensions SELECT * FROM {SOURCE}.pnn_extensions;"
            f"DELETE FROM {DATABASE}.pnn_menu WHERE menutype='mainmenu' AND alias='ampuh';"
            f"SET @edge := (SELECT MAX(rgt) FROM {DATABASE}.pnn_menu); UPDATE {DATABASE}.pnn_menu SET rgt=rgt+8 WHERE rgt>@edge;"
            f"INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Legacy AMPUH','ampuh','','ampuh','index.php?option=com_content&view=article&id=1','component',0,1,1,extension_id,NULL,NULL,0,1,'',0,'{{}}',@edge+1,@edge+4,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; SET @canonical := LAST_INSERT_ID();"
            f"INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','AMPUH child','ampuh-child','','ampuh/child','index.php?option=com_content&view=article&id=1','component',1,@canonical,2,extension_id,NULL,NULL,0,1,'',0,'{{}}',@edge+2,@edge+3,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1;"
            f"INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Duplicate AMPUH','ampuh','','ampuh','index.php?option=com_content&view=article&id=1','component',1,1,1,extension_id,NULL,NULL,0,1,'',0,'{{}}',@edge+5,@edge+8,0,'id-ID',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1; SET @duplicate := LAST_INSERT_ID();"
            f"INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Duplicate child','duplicate-child','','ampuh/duplicate-child','index.php?option=com_content&view=article&id=1','component',1,@duplicate,2,extension_id,NULL,NULL,0,1,'',0,'{{}}',@edge+6,@edge+7,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1;"
        )
        canonical = mysql("SELECT id FROM pnn_menu WHERE title='Legacy AMPUH';", DATABASE)
        hidden = mysql("SELECT id,title,alias,path,parent_id,language,client_id FROM pnn_menu WHERE menutype='hidden' AND alias='ampuh' AND parent_id=1;", DATABASE)
        with tempfile.TemporaryDirectory() as raw:
            directory = Path(raw); (directory / MIGRATION.name).write_text(MIGRATION.read_text(encoding="utf-8"), encoding="utf-8")
            apply(directory); stable = assert_contract(canonical, hidden, None)
            for _ in range(3):
                apply(directory, True); assert_contract(canonical, hidden, stable)
        print("AMPUH mainmenu migration contract: ok")
        return 0
    finally: mysql(f"DROP DATABASE IF EXISTS {DATABASE};")
if __name__ == "__main__":
    try: raise SystemExit(main())
    except (OSError, RuntimeError) as exc: print(f"error: {exc}", file=sys.stderr); raise SystemExit(1)
