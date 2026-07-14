#!/usr/bin/env python3
"""Pull and deploy PN Natuna staging with guarded optional full DB reset."""
import argparse
import gzip
import importlib.util
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
_builder_spec = importlib.util.spec_from_file_location("build_deploy_package", ROOT / "tools" / "build-deploy-package.py")
if not _builder_spec or not _builder_spec.loader:
    raise RuntimeError("deployment allowlist module unavailable")
_builder = importlib.util.module_from_spec(_builder_spec)
_builder_spec.loader.exec_module(_builder)

PACKAGE_ALLOW_DIRS = set(_builder.ALLOW_DIRS)
PACKAGE_ALLOW_ROOT_FILES = set(_builder.ALLOW_ROOT_FILES)
CONTENT_OWNED_DIRS = {"images", "files", "media"}
CODE_OWNED_DIRS = PACKAGE_ALLOW_DIRS - CONTENT_OWNED_DIRS
STAGING_MARKER = ".pn-natuna-staging"
EXPECTED_HOST = "new.pn-natuna.go.id"


def run(command, cwd=None, stdin=None, stdout=None):
    capture_stdout = subprocess.PIPE if stdout is None else stdout
    result = subprocess.run(command, cwd=cwd, stdin=stdin, stdout=capture_stdout, stderr=subprocess.PIPE, universal_newlines=stdin is None)
    if result.returncode:
        error = result.stderr.decode("utf-8", "replace") if isinstance(result.stderr, bytes) else result.stderr
        raise RuntimeError(error.strip() or "command failed: {}".format(command[0]))
    return result.stdout.strip() if isinstance(result.stdout, str) else ""


def require_staging_target(target):
    marker = target / STAGING_MARKER
    if not target.is_dir() or not marker.is_file():
        raise RuntimeError(f"staging marker missing: {marker}")
    if marker.read_text(encoding="utf-8").strip() != EXPECTED_HOST:
        raise RuntimeError(f"staging marker must contain {EXPECTED_HOST}")
    if target.name == "public_html" or target.resolve() == ROOT.resolve():
        raise RuntimeError("refusing production or source target")
    if not (target / "configuration.php").is_file():
        raise RuntimeError("staging configuration.php missing")


def source_files(source, top):
    root = source / top
    if not root.exists():
        return {}
    result = {}
    for path in root.rglob("*"):
        if path.is_file() and not path.is_symlink():
            rel = path.relative_to(source)
            if _builder.allowed(_builder.PurePosixPath(rel.as_posix())):
                result[rel] = path
    return result


def copy_files(files, target):
    for rel, source in files.items():
        destination = target / rel
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, destination)


def mirror_code_dir(source, target, top):
    desired = source_files(source, top)
    destination_root = target / top
    if destination_root.exists():
        for current in destination_root.rglob("*"):
            if not current.is_file() or current.is_symlink():
                continue
            rel = current.relative_to(target)
            # Delete only paths owned by the package allowlist. Runtime/log/private files survive.
            if _builder.allowed(_builder.PurePosixPath(rel.as_posix())) and rel not in desired:
                current.unlink()
    copy_files(desired, target)


def sync_tree(source, target):
    require_staging_target(target)
    for top in sorted(CODE_OWNED_DIRS):
        mirror_code_dir(source, target, top)
    for top in sorted(CONTENT_OWNED_DIRS):
        copy_files(source_files(source, top), target)
    for name in sorted(PACKAGE_ALLOW_ROOT_FILES):
        source_file = source / name
        destination = target / name
        if source_file.is_file() and _builder.allowed(_builder.PurePosixPath(name)):
            shutil.copy2(source_file, destination)
        elif destination.is_file():
            destination.unlink()


def git_update(source, branch):
    status = run(["git", "status", "--porcelain"], cwd=source)
    if status:
        raise RuntimeError("source checkout is not clean; refusing pull")
    run(["git", "pull", "--ff-only", "origin", branch], cwd=source)
    return run(["git", "rev-parse", "--short", "HEAD"], cwd=source)

def verify_gzip_sql(path):
    if not path.is_file() or path.stat().st_size < 32:
        return False
    try:
        with gzip.open(str(path), "rb") as stream:
            content = stream.read()
    except (OSError, EOFError):
        return False
    probe = content.lstrip()[:512].upper()
    return len(content) >= 32 and (b"--" in probe or b"CREATE " in probe or b"INSERT " in probe)


def backup_database(mysql_config, database, backup_dir):
    backup_dir.mkdir(parents=True, exist_ok=True)
    stamp = time.strftime("%Y%m%d-%H%M%S")
    output = backup_dir / "staging-db-{}.sql.gz".format(stamp)
    command = ["mysqldump", "--defaults-extra-file={}".format(mysql_config), "--single-transaction", "--skip-lock-tables", "--no-tablespaces", database]
    process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    with gzip.open(str(output), "wb", compresslevel=9) as compressed:
        while True:
            chunk = process.stdout.read(1024 * 1024)
            if not chunk:
                break
            compressed.write(chunk)
    stderr = process.stderr.read()
    returncode = process.wait()
    if returncode or not verify_gzip_sql(output):
        if output.exists():
            output.unlink()
        detail = stderr.decode("utf-8", "replace").strip()
        raise RuntimeError(detail or "database backup failed validation")
    return output


def import_database(mysql_config, database, dump):
    command = ["mysql", "--defaults-extra-file={}".format(mysql_config), "--default-character-set=utf8mb4", database]
    process = subprocess.Popen(command, stdin=subprocess.PIPE, stderr=subprocess.PIPE)
    try:
        with gzip.open(str(dump), "rb") as sql:
            while True:
                chunk = sql.read(1024 * 1024)
                if not chunk:
                    break
                process.stdin.write(chunk)
        process.stdin.close()
        stderr = process.stderr.read()
        returncode = process.wait()
    except (OSError, EOFError) as error:
        process.kill()
        process.wait()
        raise RuntimeError("database dump decompression failed: {}".format(error))
    if returncode:
        raise RuntimeError(stderr.decode("utf-8", "replace").strip() or "database import failed")


def reset_database(mysql_config, database, dump, backup_dir):
    if "staging" not in database.lower():
        raise RuntimeError("full DB reset requires a database name containing 'staging'")
    if not mysql_config.is_file() or not dump.is_file() or dump.suffix != ".gz":
        raise RuntimeError("private MySQL config or .sql.gz dump missing")
    backup = backup_database(mysql_config, database, backup_dir)
    try:
        import_database(mysql_config, database, dump)
    except RuntimeError as error:
        raise RuntimeError("database import failed; backup retained at {}: {}".format(backup, error))
    return backup


def clear_cache(target):
    for relative in (Path("cache"), Path("administrator/cache")):
        root = target / relative
        if not root.is_dir():
            continue
        for path in root.iterdir():
            if path.name == "index.html":
                continue
            if path.is_dir():
                shutil.rmtree(path)
            else:
                path.unlink()


def health_check(url):
    for path in ("/", "/ampuh"):
        request = urllib.request.Request(url.rstrip("/") + path, method="HEAD")
        try:
            with urllib.request.urlopen(request, timeout=20) as response:
                status = response.status
        except urllib.error.HTTPError as error:
            status = error.code
        if status not in (200, 301, 302, 401):
            raise RuntimeError(f"health check failed for {path}: HTTP {status}")


def self_test():
    if sys.version_info < (3, 6):
        raise RuntimeError("Python 3.6 or newer required")
    for command in ("git", "mysql", "mysqldump"):
        if not shutil.which(command):
            raise RuntimeError("required command missing: {}".format(command))
    if not PACKAGE_ALLOW_DIRS or "configuration.php" in PACKAGE_ALLOW_ROOT_FILES:
        raise RuntimeError("deployment allowlist invalid")
    print("cPanel deploy self-test: ok (Python {})".format(sys.version.split()[0]))
    return 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--target", type=Path)
    parser.add_argument("--branch", default="continue-joomla-rebuild-polish")
    parser.add_argument("--url", default="https://new.pn-natuna.go.id")
    parser.add_argument("--no-pull", action="store_true")
    parser.add_argument("--reset-database", action="store_true")
    parser.add_argument("--database")
    parser.add_argument("--mysql-config", type=Path)
    parser.add_argument("--database-dump", type=Path)
    parser.add_argument("--backup-dir", type=Path, default=Path.home() / "private" / "backups")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        return self_test()
    if not args.target:
        parser.error("--target is required unless --self-test is used")

    target = args.target.expanduser().resolve()
    require_staging_target(target)
    commit = run(["git", "rev-parse", "--short", "HEAD"], cwd=ROOT) if args.no_pull else git_update(ROOT, args.branch)
    backup = None
    if args.reset_database:
        if not args.database or not args.mysql_config or not args.database_dump:
            parser.error("--reset-database requires --database, --mysql-config, and --database-dump")
        backup = reset_database(args.mysql_config.expanduser(), args.database, args.database_dump.expanduser(), args.backup_dir.expanduser())
    sync_tree(ROOT, target)
    clear_cache(target)
    health_check(args.url)
    print(f"Staging deployed: commit {commit}")
    if backup:
        print(f"Database reset complete; backup: {backup}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError, subprocess.SubprocessError) as error:
        print(f"error: {error}", file=sys.stderr)
        raise SystemExit(1)
