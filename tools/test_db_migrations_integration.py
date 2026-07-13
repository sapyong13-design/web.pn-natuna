#!/usr/bin/env python3
"""Prove migrations reconstruct required homepage modules from an empty old state."""
from __future__ import annotations

import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
MYSQL = os.environ.get("MYSQL_BIN", r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
DATABASE = f"pn_natuna_migration_test_{os.getpid()}"
SOURCE = "pn_natuna_rebuild"


def mysql(sql: str) -> str:
    result = subprocess.run(
        [MYSQL, "-uroot", "--batch", "--skip-column-names", "--default-character-set=utf8mb4", "-e", sql],
        text=True,
        encoding="utf-8",
        capture_output=True,
    )
    if result.returncode:
        raise RuntimeError(result.stderr.strip())
    return result.stdout.strip()


def main() -> int:
    mysql(
        f"DROP DATABASE IF EXISTS {DATABASE}; CREATE DATABASE {DATABASE} CHARACTER SET utf8mb4;"
        f" CREATE TABLE {DATABASE}.pnn_modules LIKE {SOURCE}.pnn_modules;"
        f" CREATE TABLE {DATABASE}.pnn_modules_menu LIKE {SOURCE}.pnn_modules_menu;"
        f" CREATE TABLE {DATABASE}.pnn_content LIKE {SOURCE}.pnn_content;"
        f" CREATE TABLE {DATABASE}.pnn_menu LIKE {SOURCE}.pnn_menu;"
    )
    try:
        result = subprocess.run(
            [sys.executable, str(ROOT / "tools" / "apply-db-migrations.py"),
             "--database", DATABASE, "--mysql", MYSQL],
            cwd=ROOT,
        )
        if result.returncode:
            raise RuntimeError("migration runner failed")
        modules = mysql(
            f"SELECT COUNT(*), SUM(published), SUM(id=808 AND showtitle=1),"
            f" SUM(id=816 AND position='home-survey') FROM {DATABASE}.pnn_modules"
            " WHERE id IN (482,808,816,817)"
        )
        menus = mysql(
            f"SELECT COUNT(*), COUNT(DISTINCT moduleid), SUM(menuid=0)"
            f" FROM {DATABASE}.pnn_modules_menu WHERE moduleid IN (482,808,816,817)"
        )
        if modules != "4\t3\t1\t1":
            raise RuntimeError(f"canonical modules not reconstructed: {modules}")
        if menus != "4\t4\t4":
            raise RuntimeError(f"canonical module assignments not reconstructed: {menus}")
        print("empty database migration contract: ok")
        return 0
    finally:
        mysql(f"DROP DATABASE IF EXISTS {DATABASE}")


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
