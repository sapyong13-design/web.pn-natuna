#!/usr/bin/env python3
"""Build deterministic Joomla deployment ZIP from explicit allowlist."""
from __future__ import annotations

import argparse
import os
from pathlib import Path, PurePosixPath
import stat
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
ALLOW_ROOT_FILES = {
    ".htaccess", "htaccess.txt", "index.php", "LICENSE.txt", "README.txt",
    "robots.txt", "web.config.txt",
}
ALLOW_DIRS = {
    "administrator", "api", "bin", "cli", "components", "files", "images",
    "includes", "language", "layouts", "libraries", "media", "modules",
    "plugins", "templates",
}
DENY_PARTS = {".git", ".svn", "database", "docs", "tools", "cache", "logs", "tmp", ".runtime-logs"}
DENY_NAMES = {"configuration.php", ".env", ".user.ini", "php.ini", ".htpasswd"}
DENY_SUFFIXES = {
    ".sql", ".dump", ".bak", ".backup", ".old", ".orig", ".save", ".swp",
    ".log", ".pem", ".key", ".p12", ".pfx", ".zip", ".7z", ".tgz", ".gz",
}


def allowed(rel: PurePosixPath) -> bool:
    parts = rel.parts
    if not parts or parts[0] not in ALLOW_DIRS and rel.as_posix() not in ALLOW_ROOT_FILES:
        return False
    lowered = tuple(part.lower() for part in parts)
    name = lowered[-1]
    if any(part in DENY_PARTS for part in lowered) or name in DENY_NAMES:
        return False
    if name.startswith(".env") or "handoff" in name:
        return False
    return not any(name.endswith(suffix) for suffix in DENY_SUFFIXES)


def candidates() -> list[tuple[Path, PurePosixPath]]:
    result = []
    for path in ROOT.rglob("*"):
        if path.is_symlink() or not path.is_file():
            continue
        rel = PurePosixPath(path.relative_to(ROOT).as_posix())
        if allowed(rel):
            result.append((path, rel))
    return sorted(result, key=lambda item: item[1].as_posix())


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("output", nargs="?", default=str(ROOT.parent / "pn-natuna-deploy.zip"))
    parser.add_argument("--list", action="store_true", help="print package paths without writing ZIP")
    args = parser.parse_args()
    files = candidates()
    if args.list:
        print("\n".join(rel.as_posix() for _, rel in files))
        return 0
    output = Path(args.output).resolve()
    if ROOT == output or ROOT in output.parents:
        parser.error("output must be outside Joomla source tree")
    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for source, rel in files:
            info = zipfile.ZipInfo(rel.as_posix(), date_time=(2020, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = (stat.S_IFREG | 0o644) << 16
            with source.open("rb") as stream:
                archive.writestr(info, stream.read())
    print(f"Created {output} with {len(files)} files")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
