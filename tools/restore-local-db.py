#!/usr/bin/env python3
"""Import a Joomla SQL dump locally, then replay canonical project migrations."""
from __future__ import annotations

import argparse
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
IDENTIFIER = re.compile(r"^[A-Za-z0-9_]+$")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("dump", type=Path)
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--database", default="pn_natuna_rebuild")
    parser.add_argument("--prefix", default="pnn_")
    parser.add_argument("--mysql", default=os.environ.get("MYSQL_BIN", "mysql"))
    args = parser.parse_args()

    if not args.dump.is_file():
        parser.error(f"dump not found: {args.dump}")
    if not IDENTIFIER.fullmatch(args.database) or not IDENTIFIER.fullmatch(args.prefix):
        parser.error("database and prefix must contain only letters, digits, and underscore")

    command = [args.mysql, "--host", args.host, "--port", str(args.port), "--user", args.user,
               "--default-character-set=utf8mb4", args.database]
    with args.dump.open("r", encoding="utf-8-sig") as source:
        imported = subprocess.run(command, stdin=source, env=os.environ.copy())
    if imported.returncode:
        raise RuntimeError(f"dump import failed with exit code {imported.returncode}; migrations not applied")

    migration_command = [
        sys.executable, str(ROOT / "tools" / "apply-db-migrations.py"),
        "--host", args.host, "--port", str(args.port), "--user", args.user,
        "--database", args.database, "--prefix", args.prefix, "--mysql", args.mysql,
        "--reapply",
    ]
    migrated = subprocess.run(migration_command, env=os.environ.copy())
    if migrated.returncode:
        raise RuntimeError("dump imported, but canonical migrations failed")
    print(f"Local database restored and migrated: {args.database}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
