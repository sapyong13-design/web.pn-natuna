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

assert MODULE.CODE_OWNED_DIRS == MODULE.PACKAGE_ALLOW_DIRS - MODULE.CONTENT_OWNED_DIRS
assert MODULE.CONTENT_OWNED_DIRS == {"images", "files", "media"}
assert "configuration.php" not in MODULE.PACKAGE_ALLOW_ROOT_FILES
assert {"cache", "logs", "tmp", "database", "docs", "tools"}.isdisjoint(MODULE.PACKAGE_ALLOW_DIRS)

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

    MODULE.sync_tree(source, target)
    assert (target / "templates" / "site" / "new.css").read_text() == "new"
    assert not (target / "templates" / "site" / "stale.css").exists()
    assert (target / "images" / "tracked.webp").exists()
    assert (target / "images" / "uploaded.webp").exists()
    assert (target / "configuration.php").read_text() == "private"
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
