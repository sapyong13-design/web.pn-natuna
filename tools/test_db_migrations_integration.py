#!/usr/bin/env python3
"""Prove module migrations reconstruct required homepage modules from an empty old state.

Ruang lingkup sengaja dibatasi pada migrasi yang menyentuh `#__modules`. Migrasi
menu (AMPUH, navigasi mobile, submenu kesekretariatan) beroperasi di atas pohon
`#__menu` Joomla yang sudah ada -- 20260717 misalnya membaca `rgt` menu
Transparansi id=108 dan membatalkan diri lewat CHECK guard bila tidak ada. Premis
"database kosong" tidak berlaku untuk keluarga itu, dan memaksanya ke sini hanya
menghasilkan merah yang tidak menunjuk cacat apa pun. Integritas nested-set menu
dijaga terpisah oleh test_ampuh_mainmenu_migration.py dan
test_mobile_menu_migration.py, yang menyemai pohon nyata.
"""
from __future__ import annotations

import os
from pathlib import Path
import subprocess
import sys
import shutil
import tempfile

ROOT = Path(__file__).resolve().parents[1]
MYSQL = os.environ.get("MYSQL_BIN", r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
DATABASE = f"pn_natuna_migration_test_{os.getpid()}"
SOURCE = "pn_natuna_rebuild"


# Migrasi yang benar-benar membangun ulang modul beranda. Daftar eksplisit,
# bukan glob: menambah migrasi modul baru wajib disertai keputusan sadar apakah
# ia termasuk kontrak "bangun ulang dari kosong".
MODULE_MIGRATIONS = (
    "20260713_restore_homepage_modules.sql",
    "20260715_upsert_homepage_modules.sql",
    "20260716_public_facility_documentary_photos.sql",
    "20260724_polish_homepage_maklumat_card.sql",
    "20260808_polish_service_hours_card.sql",
    "20260809_compact_service_hours_heading.sql",
    "20260818_make_brand_heading_contextual.sql",
    "20260822_optimize_service_logo_assets.sql",
    "20260823_repair_lazy_map_attribute.sql",
    "20260824_optimize_brand_logo_assets.sql",
    "20260825_optimize_maklumat_thumbnail_assets.sql",
    "20261009_enable_public_page_cache_and_lazy_assets.sql",
)

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
        # Barisnya ikut disalin, bukan hanya strukturnya: guard CHECK migrasi
        # mencari `extension_id` com_content, dan tabel kosong membuat guard itu
        # menggagalkan runner (`ampuh_dependency_check_chk_1`).
        f" CREATE TABLE {DATABASE}.pnn_extensions LIKE {SOURCE}.pnn_extensions;"
        f" INSERT INTO {DATABASE}.pnn_extensions SELECT * FROM {SOURCE}.pnn_extensions;"
    )
    try:
        with tempfile.TemporaryDirectory() as staging:
            staged = Path(staging)
            for name in MODULE_MIGRATIONS:
                source = ROOT / "database" / "migrations" / name
                if not source.is_file():
                    raise RuntimeError(f"migrasi modul hilang dari rantai: {name}")
                shutil.copy2(source, staged / name)
            result = subprocess.run(
                [sys.executable, str(ROOT / "tools" / "apply-db-migrations.py"),
                 "--database", DATABASE, "--mysql", MYSQL, "--migrations", str(staged)],
                cwd=ROOT,
            )
        if result.returncode:
            raise RuntimeError("migration runner failed")
        # `showtitle` modul 808 diharapkan 0, bukan 1: migrasi
        # 20260724_polish_homepage_maklumat_card menyetelnya nol supaya chrome
        # kartu hanya menampilkan kicker "Layanan Publik" tanpa heading kembar.
        # Asersi lama masih menuntut 1 dan sudah merah sejak migrasi itu masuk.
        modules = mysql(
            f"SELECT COUNT(*), SUM(published), SUM(id=808 AND showtitle=0),"
            f" SUM(id=816 AND position='home-survey') FROM {DATABASE}.pnn_modules"
            " WHERE id IN (482,808,816,817)"
        )
        menus = mysql(
            f"SELECT COUNT(*), COUNT(DISTINCT moduleid), SUM(menuid=0)"
            f" FROM {DATABASE}.pnn_modules_menu WHERE moduleid IN (482,808,816,817)"
        )
        page_cache = mysql(
            f"SELECT enabled, params FROM {DATABASE}.pnn_extensions"
            " WHERE type='plugin' AND folder='system' AND element='cache'"
        )
        if modules != "4\t3\t1\t1":
            raise RuntimeError(f"canonical modules not reconstructed: {modules}")
        if menus != "4\t4\t4":
            raise RuntimeError(f"canonical module assignments not reconstructed: {menus}")
        if page_cache != '1\t{"browsercache":"0","cachetime":"15"}':
            raise RuntimeError(f"public page cache not enabled server-side: {page_cache}")
        print("empty database module migration contract: ok")
        return 0
    finally:
        mysql(f"DROP DATABASE IF EXISTS {DATABASE}")


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
