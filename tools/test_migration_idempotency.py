#!/usr/bin/env python3
"""Reject replay-unsafe SQL REPLACE migrations without requiring a database."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MIGRATIONS = ROOT / "database" / "migrations"
REPAIR = "20260823_repair_lazy_map_attribute.sql"
KNOWN_LEGACY = {
    "20260731_lazy_home_map.sql",
    "20260801_lazy_home_map_canonical.sql",
    "20260809_compact_service_hours_heading.sql",
}
# SQL literals use doubled single quotes for an embedded quote. This deliberately
# scans only literal-to-literal REPLACE calls, where substring replay is provable.
REPLACE_CALL = re.compile(
    r"\bREPLACE\s*\(\s*[^,]+\s*,\s*'((?:''|[^'])*)'\s*,\s*'((?:''|[^'])*)'\s*\)",
    re.IGNORECASE | re.DOTALL,
)


def unquote_sql(value: str) -> str:
    return value.replace("''", "'")


def dangerous_replaces(path: Path) -> list[tuple[str, str]]:
    sql = path.read_text(encoding="utf-8")
    return [
        (unquote_sql(match.group(1)), unquote_sql(match.group(2)))
        for match in REPLACE_CALL.finditer(sql)
        if unquote_sql(match.group(1)) in unquote_sql(match.group(2))
    ]


def main() -> int:
    assert (MIGRATIONS / REPAIR).is_file(), f"Required repair migration missing: {REPAIR}"
    offenders = [
        (path.name, source, replacement)
        for path in sorted(MIGRATIONS.glob("*.sql"))
        for source, replacement in dangerous_replaces(path)
        if path.name not in KNOWN_LEGACY
    ]
    assert not offenders, (
        "Replay-unsafe REPLACE found. REPLACE(column, 'A', 'B') is unsafe when A "
        "is contained in B: every replay can add another prefix or fragment. Use an "
        "idempotent predicate or REGEXP_REPLACE normalization instead. Offenders: "
        + ", ".join(name for name, _, _ in offenders)
    )
    print("Migration idempotency contract: ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
