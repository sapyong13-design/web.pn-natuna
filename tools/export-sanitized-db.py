#!/usr/bin/env python3
"""Export Joomla schema plus non-sensitive rows without exposing DB password."""
from __future__ import annotations
import argparse
import os
from pathlib import Path
import re
import subprocess
import sys

SENSITIVE_TABLES = (
    "action_logs", "action_logs_extensions", "action_logs_users", "privacy_consents",
    "privacy_requests", "session", "ucm_history", "user_keys", "user_mfa",
    "user_notes", "user_profiles", "user_usergroup_map", "users",
)
IDENTIFIER = re.compile(r"^[A-Za-z0-9_]+$")


def build_commands(binary: str, host: str, port: int, user: str, database: str, prefix: str):
    if not IDENTIFIER.fullmatch(database) or not IDENTIFIER.fullmatch(prefix):
        raise ValueError("database and prefix must contain only letters, digits, and underscore")
    common = [binary, "--host", host, "--port", str(port), "--user", user,
              "--single-transaction", "--skip-lock-tables", "--default-character-set=utf8mb4",
              "--set-gtid-purged=OFF", "--no-tablespaces", database]
    schema = common[:-1] + ["--no-data", common[-1]]
    data = common[:-1] + ["--no-create-info", "--skip-triggers"]
    data.extend(f"--ignore-table={database}.{prefix}{table}" for table in SENSITIVE_TABLES)
    data.append(database)
    return schema, data


def validate_dump(text: str, prefix: str) -> None:
    names = "|".join(re.escape(prefix + table) for table in SENSITIVE_TABLES)
    if re.search(rf"(?im)^INSERT\s+INTO\s+`?(?:{names})`?\b", text):
        raise RuntimeError("sanitized dump contains sensitive table rows")


def run(command: list[str], env: dict[str, str]) -> str:
    result = subprocess.run(command, env=env, text=True, encoding="utf-8", stdout=subprocess.PIPE)
    if result.returncode:
        raise RuntimeError(f"mysqldump failed with exit code {result.returncode}")
    return result.stdout


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("output", type=Path)
    parser.add_argument("--host", required=True)
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", required=True)
    parser.add_argument("--database", required=True)
    parser.add_argument("--prefix", required=True)
    parser.add_argument("--mysqldump", default="mysqldump")
    args = parser.parse_args()
    password = os.environ.get("MYSQL_PWD")
    if not password:
        parser.error("set MYSQL_PWD in private CLI environment; password arguments are forbidden")
    if args.output.suffix.lower() != ".sql":
        parser.error("output must use .sql extension")
    schema, data = build_commands(args.mysqldump, args.host, args.port, args.user, args.database, args.prefix)
    env = os.environ.copy()
    text = run(schema, env) + "\n" + run(data, env)
    validate_dump(text, args.prefix)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(text, encoding="utf-8", newline="\n")
    print(f"Created sanitized export: {args.output} (sensitive table rows excluded)")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError, ValueError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        raise SystemExit(1)
