#!/usr/bin/env python3
"""Laporan internal statistik pencarian Joomla."""
from __future__ import annotations

import argparse
import csv
import os
from pathlib import Path
import re
import subprocess
import sys
from typing import Iterable

ROOT = Path(__file__).resolve().parents[1]
IDENTIFIER = re.compile(r"^[A-Za-z0-9_]+$")
CONFIG_VALUE = re.compile(r"public \$(host|user|password|db|dbprefix)\s*=\s*'((?:\\'|[^'])*)';")
EMPTY_MESSAGE = (
    "Belum ada statistik pencarian. Pencatatan baru diaktifkan pada 2 Agu 2026; "
    "data akan terkumpul dari lalu lintas pengunjung langsung."
)


def validate_identifier(value: str, label: str) -> str:
    if not IDENTIFIER.fullmatch(value):
        raise ValueError(f"{label} hanya boleh berisi huruf, angka, dan garis bawah")
    return value


def read_configuration(path: Path) -> dict[str, str]:
    if not path.is_file():
        raise RuntimeError(f"configuration.php tidak ditemukan: {path}")
    values = {name: value.replace("\\'", "'") for name, value in CONFIG_VALUE.findall(path.read_text(encoding="utf-8"))}
    required = {"host", "user", "password", "db", "dbprefix"}
    missing = required - values.keys()
    if missing:
        raise RuntimeError("configuration.php tidak memuat: " + ", ".join(sorted(missing)))
    return values


def mysql_command(args: argparse.Namespace, config: dict[str, str]) -> tuple[list[str], dict[str, str]]:
    command = [args.mysql]
    if args.mysql_defaults_file is not None:
        defaults_file = args.mysql_defaults_file.expanduser().resolve()
        if not defaults_file.is_file():
            raise ValueError(f"Berkas pengaturan MySQL tidak ditemukan: {defaults_file}")
        command.append(f"--defaults-extra-file={defaults_file}")
    command.extend(["--host", args.host or config["host"], "--batch", "--skip-column-names", "--default-character-set=utf8mb4"])
    if args.user is not None:
        command.extend(["--user", args.user])
    elif args.mysql_defaults_file is None and config["user"]:
        command.extend(["--user", config["user"]])
    command.append(args.database or config["db"])
    env = os.environ.copy()
    if args.mysql_defaults_file is None and config["password"]:
        env["MYSQL_PWD"] = config["password"]
    return command, env


def run_mysql(command: list[str], sql: str, env: dict[str, str]) -> list[list[str]]:
    result = subprocess.run(command, input=sql, text=True, encoding="utf-8", capture_output=True, env=env)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or f"mysql gagal dengan kode {result.returncode}")
    return [line.split("\t") for line in result.stdout.splitlines() if line]


def rows_to_data(rows: Iterable[list[str]]) -> list[tuple[str, int, int]]:
    data: list[tuple[str, int, int]] = []
    for row in rows:
        if len(row) != 3:
            raise RuntimeError("Respons MySQL tidak sesuai format laporan")
        data.append((row[0], int(row[1]), int(row[2])))
    return data


def format_table(headers: tuple[str, ...], rows: list[tuple[str, ...]]) -> list[str]:
    widths = [len(header) for header in headers]
    for row in rows:
        for index, value in enumerate(row):
            widths[index] = max(widths[index], len(value))
    return ["  ".join(value.ljust(widths[index]) for index, value in enumerate(row)) for row in [headers, *rows]]


def write_csv(path: Path, zero_rows: list[tuple[str, int, int]], top_rows: list[tuple[str, int, int]], summary: tuple[int, int, int]) -> None:
    with path.open("w", newline="", encoding="utf-8") as output:
        writer = csv.writer(output)
        writer.writerow(["bagian", "kueri", "pencarian", "hasil"])
        for term, hits, results in zero_rows:
            writer.writerow(["tidak_ada_hasil", term, hits, results])
        for term, hits, results in top_rows:
            writer.writerow(["kueri_terbanyak", term, hits, results])
        writer.writerow(["ringkasan", "istilah_unik", summary[0], ""])
        writer.writerow(["ringkasan", "total_pencarian", summary[1], ""])
        writer.writerow(["ringkasan", "pencarian_tanpa_hasil", summary[2], ""])


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--mysql", default=os.environ.get("MYSQL_BIN", "mysql"))
    parser.add_argument("--mysql-defaults-file", type=Path, default=Path(os.environ["MYSQL_DEFAULTS_FILE"]) if os.environ.get("MYSQL_DEFAULTS_FILE") else None, help="berkas opsi MySQL privat")
    parser.add_argument("--database")
    parser.add_argument("--host")
    parser.add_argument("--user")
    parser.add_argument("--prefix")
    parser.add_argument("--limit", type=int, default=20)
    parser.add_argument("--csv", type=Path, help="ekspor CSV ke lokasi privat di luar root Joomla")
    args = parser.parse_args()
    if args.limit < 1:
        raise ValueError("--limit harus paling sedikit 1")

    if args.csv is not None:
        csv_path = args.csv.expanduser().resolve()
        try:
            csv_path.relative_to(ROOT.resolve())
        except ValueError:
            pass
        else:
            raise ValueError("CSV ditolak: istilah pencarian dapat memuat nama pihak dan tidak boleh menjadi dapat diakses dari web")
    else:
        csv_path = None

    config = read_configuration(ROOT / "configuration.php")
    prefix = validate_identifier(args.prefix or config["dbprefix"], "Prefix tabel")
    database = validate_identifier(args.database or config["db"], "Nama database")
    args.database = database
    table = f"`{prefix}finder_logging`"
    command, env = mysql_command(args, config)

    summary_rows = run_mysql(command, f"SELECT COUNT(*), COALESCE(SUM(hits), 0), COALESCE(SUM(CASE WHEN results = 0 THEN hits ELSE 0 END), 0) FROM {table};", env)
    if len(summary_rows) != 1 or len(summary_rows[0]) != 3:
        raise RuntimeError("Respons MySQL tidak sesuai format ringkasan")
    distinct_terms, total_searches, zero_searches = (int(value) for value in summary_rows[0])
    if distinct_terms == 0:
        print(EMPTY_MESSAGE)
        return 0

    zero_rows = rows_to_data(run_mysql(command, f"SELECT searchterm, hits, results FROM {table} WHERE results = 0 ORDER BY hits DESC, searchterm ASC LIMIT {args.limit};", env))
    top_rows = rows_to_data(run_mysql(command, f"SELECT searchterm, hits, results FROM {table} ORDER BY hits DESC, searchterm ASC LIMIT {args.limit};", env))
    percentage = (zero_searches * 100 / total_searches) if total_searches else 0

    print("Kueri yang tidak menemukan apa pun")
    if zero_rows:
        print("\n".join(format_table(("Kueri", "Dicari"), [(term, str(hits)) for term, hits, _ in zero_rows])))
    else:
        print("Tidak ada kueri tanpa hasil.")
    print("\nKueri terbanyak")
    print("\n".join(format_table(("Kueri", "Dicari", "Hasil"), [(term, str(hits), str(results)) for term, hits, results in top_rows])))
    print("\nRingkasan")
    print(f"Istilah unik             : {distinct_terms}")
    print(f"Total pencarian          : {total_searches}")
    print(f"Pencarian tanpa hasil    : {zero_searches} ({percentage:.1f}%)")

    if csv_path is not None:
        write_csv(csv_path, zero_rows, top_rows, (distinct_terms, total_searches, zero_searches))
        print(f"\nCSV internal ditulis ke: {csv_path}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, RuntimeError, ValueError) as exc:
        print(f"galat: {exc}", file=sys.stderr)
        raise SystemExit(1)
