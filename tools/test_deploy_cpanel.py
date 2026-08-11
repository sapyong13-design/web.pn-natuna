"""Focused policy tests for guarded cPanel staging deployment."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import tempfile
from io import BytesIO
from unittest.mock import patch
import ast

ROOT = Path(__file__).resolve().parents[1]
for script in (ROOT / "tools" / "build-deploy-package.py", ROOT / "tools" / "deploy-cpanel.py"):
    script_source = script.read_text(encoding="utf-8")
    ast.parse(script_source, filename=str(script), feature_version=(3, 6))
    for unsupported in ("from __future__ import annotations", "list[", "dict[", " | None", "text=True", "capture_output=", "missing_ok="):
        assert unsupported not in script_source, "{} uses unsupported Python 3.6 syntax/API: {}".format(script, unsupported)
SPEC = spec_from_file_location("deploy_cpanel", ROOT / "tools" / "deploy-cpanel.py")
assert SPEC and SPEC.loader
MODULE = module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)
import subprocess
help_result = subprocess.run(["python", str(ROOT / "tools" / "deploy-cpanel.py"), "--help"], stdout=subprocess.PIPE, stderr=subprocess.PIPE, universal_newlines=True)
assert help_result.returncode == 0, help_result.stderr
assert "--full-staging" in help_result.stdout

assert MODULE.CODE_OWNED_DIRS == MODULE.PACKAGE_ALLOW_DIRS - MODULE.CONTENT_OWNED_DIRS
assert MODULE.CONTENT_OWNED_DIRS == {"images", "files", "media"}
assert "configuration.php" not in MODULE.PACKAGE_ALLOW_ROOT_FILES
assert {"cache", "logs", "tmp", "database", "docs", "tools"}.isdisjoint(MODULE.PACKAGE_ALLOW_DIRS)
assert MODULE._builder.allowed(__import__('pathlib').PurePosixPath('libraries/vendor/joomla/database/src/DatabaseAwareTrait.php'))
assert not MODULE._builder.allowed(__import__('pathlib').PurePosixPath('database/migrations/secret.sql'))
assert MODULE._builder.allowed(__import__('pathlib').PurePosixPath('administrator/components/com_cache/src/View/Cache/HtmlView.php'))
assert MODULE._builder.allowed(__import__('pathlib').PurePosixPath('media/templates/site/cassiopeia/scss/tools/_tools.scss'))
assert not MODULE._builder.allowed(__import__('pathlib').PurePosixPath('administrator/cache/autoload_psr4.php'))
assert not MODULE._builder.allowed(__import__('pathlib').PurePosixPath('administrator/logs/joomla_update.php'))
for htaccess in (ROOT / ".htaccess", ROOT / "htaccess.txt"):
    htaccess_source = htaccess.read_text(encoding="utf-8")
    assert "<IfModule mod_setenvif.c>" in htaccess_source
    assert "SetEnvIf Host ^new\\.pn-natuna\\.go\\.id$ STAGING" in htaccess_source
    assert 'X-Robots-Tag "noindex, nofollow, noarchive" env=STAGING' in htaccess_source
    assert 'Strict-Transport-Security "max-age=604800" env=HTTPS' in htaccess_source
    assert 'Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=(), usb=()"' in htaccess_source
    assert "Header always unset X-Powered-By" in htaccess_source
    assert "RewriteCond %{HTTPS} !=on" in htaccess_source
    assert "RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]" in htaccess_source
    assert "RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L,NE]" in htaccess_source
    assert 'Content-Security-Policy "default-src \'self\'' in htaccess_source
    assert "Content-Security-Policy-Report-Only" not in htaccess_source
    assert "https://www.youtube-nocookie.com" in htaccess_source
    assert "RewriteRule ^api(?:/|$) - [F,END,NC]" in htaccess_source
    assert "RewriteRule ^(?:images|files|cache|tmp)/.*\\.(?:php[0-9]?|phtml|phar)$ - [F,END,NC]" in htaccess_source
    assert "<IfModule LiteSpeed>" in htaccess_source
    assert "CacheLookup on" in htaccess_source
    assert "CacheEnable public" not in htaccess_source
    for canonical_image in (
        "images/berita/2026/alih-tugas-cania-kirana-1",
        "images/berita/2026/alih-tugas-cania-kirana-2",
        "images/berita/2026/bola-voli-hut-81-ri-ma-1",
        "images/berita/2026/bola-voli-hut-81-ri-ma-2",
        "images/berita/2026/bola-voli-hut-81-ri-ma-3",
        "images/berita/2026/mobile-legends-hut-81-ri-ma-1",
        "images/berita/2026/mobile-legends-hut-81-ri-ma-2",
        "images/berita/2026/mobile-legends-hut-81-ri-ma-3",
    ):
        assert canonical_image in htaccess_source
    assert "IMG_3701" in htaccess_source
    assert r"WhatsApp\x20Image\x202026-07-31" in htaccess_source
for extension_file in (
    ROOT / "plugins" / "system" / "lscache" / "lscache.php",
    ROOT / "plugins" / "system" / "lscache" / "lscache.xml",
    ROOT / "administrator" / "components" / "com_lscache" / "config.xml",
    ROOT / "components" / "com_lscache" / "lscache.php",
):
    assert extension_file.is_file(), "LiteSpeed extension file missing: {}".format(extension_file)
assert "<version>1.5.2-pn.1</version>" in (ROOT / "plugins" / "system" / "lscache" / "lscache.xml").read_text(encoding="utf-8")
config_values = MODULE.read_joomla_database_config("""<?php
public $user = 'stage_user';
public $password = 'ignored-here';
public $db = 'site_staging';
""")
assert config_values == {"user": "stage_user", "db": "site_staging"}

with tempfile.TemporaryDirectory() as folder:
    base = Path(folder)
    source = base / "source"
    target = base / "new.pn-natuna.go.id"
    source.mkdir()
    target.mkdir()
    (target / MODULE.STAGING_MARKER).write_text("new.pn-natuna.go.id\n", encoding="utf-8")
    (target / "configuration.php").write_text("private", encoding="utf-8")

    (source / "templates" / "site").mkdir(parents=True)
    (source / "templates" / "site" / "new.css").write_text("new", encoding="utf-8")
    (target / "templates" / "site").mkdir(parents=True)
    (target / "templates" / "site" / "stale.css").write_text("stale", encoding="utf-8")

    (source / "images").mkdir()
    (source / "images" / "tracked.webp").write_text("tracked", encoding="utf-8")
    (target / "images").mkdir()
    (target / "images" / "uploaded.webp").write_text("upload", encoding="utf-8")
    (target / ".htaccess").write_text('AuthType Basic\nAuthName "Private"\nAuthUserFile "/home/test/passwd"\nRequire valid-user\n', encoding="utf-8")
    (source / ".htaccess").write_text("Options -Indexes\n", encoding="utf-8")

    MODULE.sync_tree(source, target)
    assert (target / "templates" / "site" / "new.css").read_text() == "new"
    assert not (target / "templates" / "site" / "stale.css").exists()
    assert (target / "images" / "tracked.webp").exists()
    assert (target / "images" / "uploaded.webp").exists()
    assert (target / "configuration.php").read_text() == "private"
    deployed_htaccess = (target / ".htaccess").read_text(encoding="utf-8")
    assert deployed_htaccess.startswith("Options -Indexes")
    assert 'AuthType Basic' in deployed_htaccess
    assert 'AuthUserFile "/home/test/passwd"' in deployed_htaccess
    assert 'Require valid-user' in deployed_htaccess
class FakeDumpProcess:
    def __init__(self):
        self.stdout = BytesIO(b"-- MariaDB-safe backup\nCREATE TABLE test (id int);\n")
        self.stderr = BytesIO()
        self.returncode = None

    def wait(self):
        self.returncode = 0
        return 0


with tempfile.TemporaryDirectory() as folder:
    base = Path(folder)
    config = base / "staging.cnf"
    config.write_text("[client]\nuser=test\n", encoding="utf-8")
    with patch.object(MODULE.subprocess, "Popen", return_value=FakeDumpProcess()):
        backup = MODULE.backup_database(config, "site_staging", base / "backups")
    assert MODULE.verify_gzip_sql(backup)
    import gzip
    with gzip.open(str(backup), "rb") as stream:
        restored = stream.read()
    assert restored.startswith(b"-- MariaDB-safe backup")
    assert b"CREATE TABLE test" in restored
class FakeImportStdin:
    def __init__(self):
        self.content = bytearray()

    def write(self, chunk):
        self.content.extend(chunk)

    def close(self):
        pass


class FakeImportProcess:
    def __init__(self):
        self.stdin = FakeImportStdin()
        self.stderr = BytesIO()

    def wait(self):
        return 0


with tempfile.TemporaryDirectory() as folder:
    base = Path(folder)
    dump = base / "current.sql.gz"
    import gzip
    with gzip.open(str(dump), "wb") as stream:
        stream.write(b"CREATE TABLE imported (id int);\n")
    config = base / "staging.cnf"
    config.write_text("[client]\nuser=test\n", encoding="utf-8")
    process = FakeImportProcess()
    with patch.object(MODULE.subprocess, "Popen", return_value=process):
        MODULE.import_database(config, "site_staging", dump)
    assert bytes(process.stdin.content) == b"CREATE TABLE imported (id int);\n"
    assert not bytes(process.stdin.content).startswith(b"\x1f\x8b")


with tempfile.TemporaryDirectory() as folder:
    unsafe = Path(folder) / "public_html"
    unsafe.mkdir()
    try:
        MODULE.require_staging_target(unsafe)
    except RuntimeError as error:
        assert "staging marker" in str(error).lower()
    else:
        raise AssertionError("production-like target must be rejected")

print("cPanel staging deploy policy: ok")
