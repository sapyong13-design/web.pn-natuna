"""Focused policy tests for guarded cPanel staging deployment."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import tempfile

ROOT = Path(__file__).resolve().parents[1]
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
