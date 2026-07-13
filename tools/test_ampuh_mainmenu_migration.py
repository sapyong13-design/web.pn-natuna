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
    if database:
        command.append(database)
    command.extend(["-e", sql])
    result = subprocess.run(command, text=True, encoding="utf-8", capture_output=True)
    if result.returncode:
        raise RuntimeError(result.stderr.strip())
    return result.stdout.strip()


def apply(migrations: Path, reapply: bool = False) -> None:
    command = [sys.executable, str(RUNNER), "--database", DATABASE, "--mysql", MYSQL, "--migrations", str(migrations)]
    if reapply:
        command.append("--reapply")
    result = subprocess.run(command, cwd=ROOT, text=True, encoding="utf-8", capture_output=True)
    if result.returncode:
        raise RuntimeError(result.stderr.strip())


def main() -> int:
    if not MIGRATION.is_file():
        raise RuntimeError(f"missing migration: {MIGRATION.name}")
    mysql(f"DROP DATABASE IF EXISTS {DATABASE}; CREATE DATABASE {DATABASE} CHARACTER SET utf8mb4;")
    try:
        mysql(
            f"CREATE TABLE {DATABASE}.pnn_content LIKE {SOURCE}.pnn_content;"
            f"INSERT INTO {DATABASE}.pnn_content SELECT * FROM {SOURCE}.pnn_content;"
            f"CREATE TABLE {DATABASE}.pnn_menu LIKE {SOURCE}.pnn_menu;"
            f"INSERT INTO {DATABASE}.pnn_menu SELECT * FROM {SOURCE}.pnn_menu;"
            f"CREATE TABLE {DATABASE}.pnn_extensions LIKE {SOURCE}.pnn_extensions;"
            f"INSERT INTO {DATABASE}.pnn_extensions SELECT * FROM {SOURCE}.pnn_extensions;"
        )
        mysql("DELETE FROM pnn_menu WHERE menutype='mainmenu' AND alias='ampuh';", DATABASE)
        with tempfile.TemporaryDirectory() as directory:
            migrations = Path(directory)
            (migrations / MIGRATION.name).write_text(MIGRATION.read_text(encoding="utf-8"), encoding="utf-8")
            apply(migrations)
            first = mysql(
                "SELECT title, alias, path, link, published, access, parent_id, level "
                "FROM pnn_menu WHERE menutype='mainmenu' AND alias='ampuh';",
                DATABASE,
            )
            if not first.startswith("AMPUH\tampuh\tampuh\tindex.php?option=com_content&view=article&id="):
                raise RuntimeError(f"AMPUH mainmenu route missing: {first}")
            if not first.endswith("\t1\t1\t1\t1"):
                raise RuntimeError(f"AMPUH mainmenu must be public, published root item: {first}")
            sequence = mysql(
                "SELECT GROUP_CONCAT(title ORDER BY lft SEPARATOR ' > ') FROM pnn_menu "
                "WHERE menutype='mainmenu' AND parent_id=1 AND id IN (108,109) OR "
                "(menutype='mainmenu' AND parent_id=1 AND alias='ampuh');",
                DATABASE,
            )
            if sequence != "Transparansi > AMPUH > Hubungi Kami":
                raise RuntimeError(f"wrong AMPUH mainmenu sequence: {sequence}")
            nested_set = mysql(
                "SELECT transparansi.rgt, ampuh.lft, ampuh.rgt, kontak.lft "
                "FROM pnn_menu AS transparansi "
                "INNER JOIN pnn_menu AS ampuh ON ampuh.menutype='mainmenu' AND ampuh.alias='ampuh' AND ampuh.parent_id=1 "
                "INNER JOIN pnn_menu AS kontak ON kontak.id=109 WHERE transparansi.id=108;",
                DATABASE,
            )
            boundaries = [int(value) for value in nested_set.split("\t")]
            if not boundaries[0] < boundaries[1] < boundaries[2] < boundaries[3]:
                raise RuntimeError(f"AMPUH nested-set ordering invalid: {nested_set}")
            apply(migrations, reapply=True)
            replay = mysql(
                "SELECT COUNT(*), GROUP_CONCAT(title ORDER BY lft SEPARATOR ' > ') FROM pnn_menu "
                "WHERE (menutype='mainmenu' AND parent_id=1 AND id IN (108,109)) OR "
                "(menutype='mainmenu' AND parent_id=1 AND alias='ampuh');",
                DATABASE,
            )
            if replay != "3\tTransparansi > AMPUH > Hubungi Kami":
                raise RuntimeError(f"replay changed AMPUH mainmenu: {replay}")
        print("AMPUH mainmenu migration contract: ok")
        return 0
    finally:
        mysql(f"DROP DATABASE IF EXISTS {DATABASE};")


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
