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

def validate_collation(value: str) -> str:
    if not re.fullmatch(r"utf8mb4_[A-Za-z0-9_]+", value):
        raise ValueError("database collation must be a valid utf8mb4 collation")
    return value


def session_sql(sql: str, collation: str) -> str:
    validate_collation(collation)
    return f"SET NAMES utf8mb4 COLLATE {collation};\n{sql}"


def discover_migrations(directory: Path) -> list[Path]:
    return sorted(path for path in directory.glob("*.sql") if path.is_file())


def render_migration(sql: str, prefix: str) -> str:
    validate_identifier(prefix)
    return sql.replace("#__", prefix)


def migration_checksum(sql: str) -> str:
    return hashlib.sha256(sql.encode("utf-8")).hexdigest()


def mysql_command(args: argparse.Namespace) -> list[str]:
    command = [args.mysql]
    if args.mysql_defaults_file is not None:
        defaults_file = args.mysql_defaults_file.expanduser().resolve()
        if not defaults_file.is_file():
            raise ValueError(f"MySQL defaults file not found: {defaults_file}")
        command.append(f"--defaults-extra-file={defaults_file}")
    command.extend([
        "--host", args.host,
        "--port", str(args.port),
    ])
    if args.user:
        command.extend(["--user", args.user])
    command.extend([
        "--default-character-set=utf8mb4",
        "--batch",
        "--skip-column-names",
        args.database,
    ])
    return command


def run_mysql(command: list[str], sql: str, env: dict[str, str]) -> str:
    result = subprocess.run(command, input=sql, env=env, text=True, encoding="utf-8", capture_output=True)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or f"mysql failed with exit code {result.returncode}")
    return result.stdout.strip()

def database_collation(command: list[str], database: str, env: dict[str, str]) -> str:
    escaped = database.replace("'", "''")
    value = run_mysql(
        command,
        "SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA "
        f"WHERE SCHEMA_NAME='{escaped}';",
        env,
    )
    if not value:
        raise RuntimeError(f"database not found or collation unavailable: {database}")
    return validate_collation(value.splitlines()[0].strip())


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--reapply", action="store_true", help="replay idempotent migrations after importing a dump")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default=None if os.environ.get("MYSQL_DEFAULTS_FILE") else "root")
    parser.add_argument("--database", default="pn_natuna_rebuild")
    parser.add_argument("--prefix", default="pnn_")
    parser.add_argument("--mysql", default=os.environ.get("MYSQL_BIN", "mysql"))
    parser.add_argument("--mysql-defaults-file", type=Path, default=Path(os.environ["MYSQL_DEFAULTS_FILE"]) if os.environ.get("MYSQL_DEFAULTS_FILE") else None, help="private MySQL option file; credentials stay out of the process list")
    parser.add_argument("--migrations", type=Path, default=DEFAULT_MIGRATIONS)
    args = parser.parse_args()

    validate_identifier(args.database)
    validate_identifier(args.prefix)
    migrations = discover_migrations(args.migrations)
    if not migrations:
        raise RuntimeError(f"no migrations found in {args.migrations}")

    env = os.environ.copy()
    command = mysql_command(args)
    collation = database_collation(command, args.database, env)
    table = f"{args.prefix}project_migrations"
    run_mysql(command, session_sql(f"CREATE TABLE IF NOT EXISTS `{table}` (name varchar(191) NOT NULL PRIMARY KEY, checksum char(64) NOT NULL, applied_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={collation};", collation), env)
    applied = 0
    for path in migrations:
        source = path.read_text(encoding="utf-8")
        checksum = migration_checksum(source)
        escaped_name = path.name.replace("'", "''")
        current = run_mysql(command, session_sql(f"SELECT checksum FROM `{table}` WHERE name='{escaped_name}';", collation), env)
        if current:
            if current != checksum:
                raise RuntimeError(f"applied migration changed: {path.name}")
            if not args.reapply:
                print(f"skip {path.name}")
                continue
        sql = render_migration(source, args.prefix)
        record = "" if current else f"INSERT INTO `{table}` (name, checksum) VALUES ('{escaped_name}', '{checksum}');"
        run_mysql(command, session_sql(f"START TRANSACTION;\n{sql}\n{record}\nCOMMIT;", collation), env)
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
