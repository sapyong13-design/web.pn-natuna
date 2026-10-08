#!/usr/bin/env python3
"""Official Joomla schemas deploy; dumps, archives and executable media do not."""

import importlib.util
from pathlib import Path, PurePosixPath
import subprocess
import tempfile

MODULE_PATH = Path(__file__).with_name("build-deploy-package.py")
SPEC = importlib.util.spec_from_file_location("build_deploy_package", MODULE_PATH)
PACKAGE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(PACKAGE)
ROOT = MODULE_PATH.parents[1]

SCHEMAS = [
    "administrator/components/com_admin/sql/updates/{}/6.1.0-2026-03-13.sql".format(driver)
    for driver in ("mysql", "postgresql")
] + [
    "administrator/components/{}/sql/{}.mysql.utf8.sql".format(component, operation)
    for component in ("com_banners", "com_contact", "com_newsfeeds")
    for operation in ("install", "uninstall")
] + [
    "administrator/components/com_finder/sql/{}.{}.sql".format(operation, driver)
    for operation in ("install", "uninstall")
    for driver in ("mysql", "postgresql")
] + [
    "libraries/vendor/joomla/session/meta/sql/{}.sql".format(driver)
    for driver in ("mysql", "sqlite", "sqlsrv", "pgsql")
] + [
    "libraries/vendor/php-debugbar/php-debugbar/src/DebugBar/Storage/pdo_storage_schema.sql"
]
DENIED_SQL = [
    "dump.sql",
    "administrator/dump.sql",
    "administrator/components/com_admin/sql/dump.sql",
    "administrator/components/com_admin/sql/updates/mysql/dump.sql",
    "administrator/components/com_admin/sql/updates/postgresql/backup.sql",
    "administrator/components/com_admin/sql/updates/mysql/6.1.0-2026-03-13-dump.sql",
    "administrator/components/com_admin/sql/updates/mysql/backups/6.1.0-2026-03-13.sql",
    "administrator/components/com_admin/sql/updates/sqlite/6.1.0-2026-03-13.sql",
    "administrator/components/com_finder/sql/dump.sql",
    "administrator/components/com_custom/sql/install.mysql.sql",
    "libraries/vendor/joomla/session/meta/sql/dump.sql",
    "media/database.sql",
    "database/migrations/20261005_example.sql",
]
ARCHIVES = [
    "administrator/components/com_admin/sql/updates/mysql/6.1.0-2026-03-13.sql.gz",
    "administrator/backup.dump",
    "administrator/backup.tar",
    "administrator/backup.tar.gz",
    "administrator/backup.zip",
    "administrator/backup.7z",
]
UNSAFE_MEDIA = [
    "{}/upload{}".format(directory, suffix)
    for directory in ("images", "files", "media")
    for suffix in (".php", ".PHP", ".php8", ".phtml", ".phar")
]
SAFE_FILES = ["index.php", "administrator/index.php", "images/photo.webp"]

for name in SCHEMAS + SAFE_FILES:
    assert PACKAGE.allowed(PurePosixPath(name)), "Required package file denied: " + name
for name in DENIED_SQL + ARCHIVES + UNSAFE_MEDIA:
    assert not PACKAGE.allowed(PurePosixPath(name)), "Unsafe package file allowed: " + name

# --no-index also checks schema paths already tracked after the core overlay.
ignored = subprocess.run(
    ["git", "check-ignore", "--no-index", "--stdin"],
    cwd=str(ROOT),
    input=("\n".join(SCHEMAS + DENIED_SQL + ARCHIVES) + "\n").encode("utf-8"),
    stdout=subprocess.PIPE,
    stderr=subprocess.PIPE,
)
assert ignored.returncode == 0, ignored.stderr
assert set(ignored.stdout.decode("utf-8").splitlines()) == set(DENIED_SQL[:-1] + ARCHIVES), ignored.stdout

original_root = PACKAGE.ROOT
try:
    with tempfile.TemporaryDirectory() as directory:
        PACKAGE.ROOT = Path(directory)
        for name in SCHEMAS + SAFE_FILES + DENIED_SQL + ARCHIVES + UNSAFE_MEDIA:
            path = PACKAGE.ROOT / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text("fixture", encoding="utf-8")
        packaged = {rel.as_posix() for _, rel in PACKAGE.candidates()}
        assert packaged == set(SCHEMAS + SAFE_FILES), packaged
finally:
    PACKAGE.ROOT = original_root

print("deployment SQL and media safety contract: ok")
