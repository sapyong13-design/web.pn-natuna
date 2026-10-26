#!/usr/bin/env python3
"""Kontrak palet tema poster jadwal sidang.

Memastikan setiap tema punya kunci warna yang sama di JS, punya aturan CSS
[data-theme="..."] di stylesheet, dan punya tombol di markup PHP. Tanpa tes ini
tema baru bisa tampil setengah jadi (mis. `foot` tidak ada sehingga footer hitam).
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
JS = ROOT / "templates/pn_natuna_2026/js/sipp-export.js"
CSS = ROOT / "templates/pn_natuna_2026/css/template-4b123344.css"
PHP = ROOT / "templates/pn_natuna_2026/sipp-schedule.php"

REQUIRED = {
    "bg", "barTop", "court", "sub", "title", "date", "meta",
    "divider", "cardBg", "cardLine", "caseText", "lineText",
    "agendaText", "foot",
}

failures: list[str] = []


def expect(condition: bool, message: str) -> None:
    if not condition:
        failures.append(message)


def themes_from_js(source: str) -> dict[str, dict[str, str]]:
    """Ambil isi tiap objek tema; beberapa pasangan kunci bisa satu baris."""
    start = source.find("const THEMES = {")
    if start == -1:
        return {}
    body = source[start:]
    themes: dict[str, dict[str, str]] = {}
    for match in re.finditer(r"\n {4}(\w+): \{", body):
        name = match.group(1)
        open_brace = body.find("{", match.start())
        depth = 0
        for index in range(open_brace, len(body)):
            char = body[index]
            if char == "{":
                depth += 1
            elif char == "}":
                depth -= 1
                if depth == 0:
                    block = body[open_brace + 1:index]
                    values: dict[str, str] = {}
                    for entry in re.finditer(r"(\w+):\s*(\[[^\]]*\]|'[^']*')", block):
                        values[entry.group(1)] = entry.group(2)
                    themes[name] = values
                    break
    return themes


def main() -> int:
    js_source = JS.read_text(encoding="utf-8")
    css_source = CSS.read_text(encoding="utf-8")
    php_source = PHP.read_text(encoding="utf-8")

    themes = themes_from_js(js_source)
    expect(len(themes) >= 6, f"harus ada minimal 6 tema, ditemukan {len(themes)}")

    for name, theme in themes.items():
        missing = REQUIRED - set(theme)
        expect(not missing, f"tema '{name}' kehilangan kunci: {sorted(missing)}")
        bg = theme.get("bg", "")
        expect(bg.count("#") == 3, f"tema '{name}' harus punya 3 warna bg, dapat {bg!r}")
        if name != "maroon":
            expect(
                f'data-theme="{name}"' in css_source,
                f"tema '{name}' tidak punya aturan CSS [data-theme]",
            )
        expect(
            f'data-sipp-theme="{name}"' in php_source,
            f"tema '{name}' tidak punya tombol di markup",
        )

    # Tombol di markup tidak boleh menunjuk tema yang tidak ada di JS.
    for button_theme in set(re.findall(r'data-sipp-theme="(\w+)"', php_source)):
        expect(
            button_theme in themes,
            f"tombol tema '{button_theme}' tidak punya palet di THEMES",
        )

    if failures:
        print("Kontrak tema GAGAL:")
        for failure in failures:
            print(f"  - {failure}")
        return 1

    print(f"Kontrak tema: ok ({len(themes)} tema, {len(REQUIRED)} kunci warna per tema)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
