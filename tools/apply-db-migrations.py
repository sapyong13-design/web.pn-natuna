#!/usr/bin/env python3
"""Apply immutable project DB migrations after every Joomla dump import."""
from __future__ import annotations

import argparse
import hashlib
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_MIGRATIONS = ROOT / "database" / "migrations"
IDENTIFIER = re.compile(r"^[A-Za-z0-9_]+$")


def validate_identifier(value: str) -> str:
    if not IDENTIFIER.fullmatch(value):
        raise ValueError("database and prefix must contain only letters, digits, and underscore")
    return value


def discover_migrations(directory: Path) -> list[Path]:
    return sorted(path for path in directory.glob("*.sql") if path.is_file())


def render_migration(sql: str, prefix: str) -> str:
    validate_identifier(prefix)
    return sql.replace("#__", prefix)


def migration_checksum(sql: str) -> str:
    return hashlib.sha256(sql.encode("utf-8")).hexdigest()


def mysql_command(args: argparse.Namespace) -> list[str]:
    return [
        args.mysql,
        "--host", args.host,
        "--port", str(args.port),
        "--user", args.user,
        "--default-character-set=utf8mb4",
        "--batch",
        "--skip-column-names",
        args.database,
    ]


def run_mysql(command: list[str], sql: str, env: dict[str, str]) -> str:
    result = subprocess.run(command, input=sql, env=env, text=True, encoding="utf-8", capture_output=True)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or f"mysql failed with exit code {result.returncode}")
    return result.stdout.strip()


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--reapply", action="store_true", help="replay idempotent migrations after importing a dump")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--database", default="pn_natuna_rebuild")
    parser.add_argument("--prefix", default="pnn_")
    parser.add_argument("--mysql", default=os.environ.get("MYSQL_BIN", "mysql"))
    parser.add_argument("--migrations", type=Path, default=DEFAULT_MIGRATIONS)
    args = parser.parse_args()

    validate_identifier(args.database)
    validate_identifier(args.prefix)
    migrations = discover_migrations(args.migrations)
    if not migrations:
        raise RuntimeError(f"no migrations found in {args.migrations}")

    env = os.environ.copy()
    command = mysql_command(args)
    table = f"{args.prefix}project_migrations"
    run_mysql(command, f"CREATE TABLE IF NOT EXISTS `{table}` (name varchar(191) NOT NULL PRIMARY KEY, checksum char(64) NOT NULL, applied_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", env)
    applied = 0
    for path in migrations:
        source = path.read_text(encoding="utf-8")
        checksum = migration_checksum(source)
        escaped_name = path.name.replace("'", "''")
        current = run_mysql(command, f"SELECT checksum FROM `{table}` WHERE name='{escaped_name}';", env)
        if current:
            if current != checksum:
                raise RuntimeError(f"applied migration changed: {path.name}")
            if not args.reapply:
                print(f"skip {path.name}")
                continue
        sql = render_migration(source, args.prefix)
        record = "" if current else f"INSERT INTO `{table}` (name, checksum) VALUES ('{escaped_name}', '{checksum}');"
        run_mysql(command, f"START TRANSACTION;\n{sql}\n{record}\nCOMMIT;", env)
        print(f"{'reapply' if current else 'apply'} {path.name}")
        applied += 1

    print(f"Database migrations current: {applied} applied, {len(migrations) - applied} already present")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError, ValueError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
