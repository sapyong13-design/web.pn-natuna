#!/usr/bin/env python3
"""Reject replay-unsafe SQL REPLACE migrations without requiring a database."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MIGRATIONS = ROOT / "database" / "migrations"
# Migrasi yang sudah diterapkan bersifat immutable (runner menolak checksum berubah),
# jadi setiap REPLACE tak aman diperbaiki oleh migrasi repair, bukan dengan menyunting
# berkas lamanya. Setiap entri KNOWN_LEGACY wajib punya repair-nya di REPAIRS.
REPAIRS = (
    "20260823_repair_lazy_map_attribute.sql",
    "20260830_repair_geraldo_degree_replay.sql",
    "20261110_repair_career_judge_roster_cards.sql",
)
KNOWN_LEGACY = {
    "20260731_lazy_home_map.sql",
    "20260801_lazy_home_map_canonical.sql",
    "20260809_compact_service_hours_heading.sql",
    "20260826_sync_geraldo_degree_and_latest_news.sql",
    # REGEXP_REPLACE with `$1`: MySQL 8 expands it, MariaDB writes it literally and swallows
    # the whole match. Repaired by 20261110_repair_career_judge_roster_cards.sql.
    "20261108_set_career_judge_rank_iii_b.sql",
    "20261109_keep_penata_label_for_career_judges.sql",
}
# SQL literals use doubled single quotes for an embedded quote. This deliberately
# scans only literal-to-literal REPLACE calls, where substring replay is provable.
REPLACE_CALL = re.compile(
    r"\bREPLACE\s*\(\s*[^,]+\s*,\s*'((?:''|[^'])*)'\s*,\s*'((?:''|[^'])*)'\s*\)",
    re.IGNORECASE | re.DOTALL,
)


def unquote_sql(value: str) -> str:
    return value.replace("''", "'")

# MySQL 8 expands `$1` in a REGEXP_REPLACE replacement; MariaDB does not. A migration that
# relies on it silently destroys content on the production database, so the pattern is banned.
DOLLAR_BACKREFERENCE = re.compile(r"REGEXP_REPLACE\s*\(.*?'\$[0-9]", re.IGNORECASE | re.DOTALL)

def dollar_backreference(path: Path) -> bool:
    return bool(DOLLAR_BACKREFERENCE.search(path.read_text(encoding="utf-8")))


def dangerous_replaces(path: Path) -> list[tuple[str, str]]:
    sql = path.read_text(encoding="utf-8")
    return [
        (unquote_sql(match.group(1)), unquote_sql(match.group(2)))
        for match in REPLACE_CALL.finditer(sql)
        if unquote_sql(match.group(1)) in unquote_sql(match.group(2))
    ]


def main() -> int:
    for repair in REPAIRS:
        assert (MIGRATIONS / repair).is_file(), f"Required repair migration missing: {repair}"
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
    dollar_offenders = [
        path.name
        for path in sorted(MIGRATIONS.glob("*.sql"))
        if dollar_backreference(path) and path.name not in KNOWN_LEGACY
    ]
    assert not dollar_offenders, (
        "REGEXP_REPLACE with a `$1` backreference found. MySQL 8 expands it, MariaDB writes "
        "it literally and destroys the whole matched region. Use a literal replacement or "
        "`\\\\1`, which both servers understand. Offenders: " + ", ".join(dollar_offenders)
    )
    print("Migration idempotency contract: ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
