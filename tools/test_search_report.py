#!/usr/bin/env python3
"""Kontrak statis untuk laporan pencarian internal."""
from __future__ import annotations

from pathlib import Path
import ast
import re

ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "tools" / "search-report.py"


def main() -> int:
    assert SCRIPT.is_file(), "tools/search-report.py harus ada"
    source = SCRIPT.read_text(encoding="utf-8")
    ast.parse(source, filename=str(SCRIPT))
    assert not re.search(r"ORDER\s+BY\s+`?id`?", source, re.IGNORECASE), "finder_logging tidak punya kolom id"
    assert "results = 0" in source, "laporan harus mengambil kueri tanpa hasil"
    assert "relative_to(ROOT.resolve())" in source and "dapat diakses dari web" in source, "CSV dalam root Joomla harus ditolak demi privasi"
    assert not re.search(r"(?:MYSQL_PWD|password)\s*=\s*['\"](?=\S)", source), "kata sandi tidak boleh ditanamkan di skrip"
    print("Kontrak laporan pencarian: ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
