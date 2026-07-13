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

def main() -> int:
    mysql(f"DROP DATABASE IF EXISTS {DATABASE}; CREATE DATABASE {DATABASE} CHARACTER SET utf8mb4;")
    try:
        mysql(
            f"CREATE TABLE {DATABASE}.pnn_content LIKE {SOURCE}.pnn_content; INSERT INTO {DATABASE}.pnn_content SELECT * FROM {SOURCE}.pnn_content;"
            f"CREATE TABLE {DATABASE}.pnn_menu LIKE {SOURCE}.pnn_menu; INSERT INTO {DATABASE}.pnn_menu SELECT * FROM {SOURCE}.pnn_menu;"
            f"CREATE TABLE {DATABASE}.pnn_extensions LIKE {SOURCE}.pnn_extensions; INSERT INTO {DATABASE}.pnn_extensions SELECT * FROM {SOURCE}.pnn_extensions;"
            f"UPDATE {DATABASE}.pnn_menu SET alias='ampuh-hidden-fixture', path='ampuh-hidden-fixture' WHERE menutype='hidden' AND alias='ampuh' AND parent_id=1 AND language='*';"
            f"DELETE FROM {DATABASE}.pnn_menu WHERE menutype='mainmenu' AND alias='ampuh';"
            f"INSERT INTO {DATABASE}.pnn_menu (menutype,title,alias,note,path,link,type,published,parent_id,level,component_id,checked_out,checked_out_time,browserNav,access,img,template_style_id,params,lft,rgt,home,language,client_id) SELECT 'mainmenu','Legacy AMPUH','ampuh','','ampuh','index.php?option=com_content&view=article&id=1','component',0,1,1,extension_id,NULL,NULL,0,1,'',0,'{{}}',1133,1134,0,'*',0 FROM {SOURCE}.pnn_extensions WHERE element='com_content' AND type='component' LIMIT 1;"
        )
        with tempfile.TemporaryDirectory() as raw:
            migrations = Path(raw); (migrations / MIGRATION.name).write_text(MIGRATION.read_text(encoding="utf-8"), encoding="utf-8")
            apply(migrations)
            apply(migrations, reapply=True)
        result = mysql("SELECT COUNT(*), GROUP_CONCAT(CONCAT(title,':',language) ORDER BY lft SEPARATOR ' > ') FROM pnn_menu WHERE (menutype='mainmenu' AND parent_id=1 AND id IN (108,109)) OR (menutype='mainmenu' AND parent_id=1 AND alias='ampuh');", DATABASE)
        if result != "3\tTransparansi:id-ID > AMPUH:id-ID > Hubungi Kami:*": raise RuntimeError(f"wrong canonical AMPUH menu/replay order: {result}")
        bounds = mysql("SELECT t.rgt,a.lft,a.rgt,h.lft FROM pnn_menu t JOIN pnn_menu a ON a.menutype='mainmenu' AND a.alias='ampuh' JOIN pnn_menu h ON h.id=109 WHERE t.id=108;", DATABASE)
        values = [int(value) for value in bounds.split("\t")]
        if not values[0] < values[1] < values[2] < values[3]: raise RuntimeError(f"invalid root bounds: {bounds}")
        route = mysql("SELECT title,alias,path,published,access,link FROM pnn_menu WHERE menutype='mainmenu' AND alias='ampuh';", DATABASE)
        if not route.startswith("AMPUH\tampuh\tampuh\t1\t1\tindex.php?option=com_content&view=article&id="): raise RuntimeError(f"invalid public route: {route}")
        print("AMPUH mainmenu migration contract: ok")
        return 0
    finally: mysql(f"DROP DATABASE IF EXISTS {DATABASE};")
if __name__ == "__main__":
    try: raise SystemExit(main())
    except (OSError, RuntimeError) as exc:
        print(f"error: {exc}", file=sys.stderr); raise SystemExit(1)
