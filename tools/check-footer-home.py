#!/usr/bin/env python3
from __future__ import annotations

import re
import subprocess
import sys
from html import unescape
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MYSQL = Path(r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
CSS = ROOT / "templates" / "pn_natuna_2026" / "css" / "template.css"
POSITIONS = ("footer-contact", "footer-links", "footer-social", "footer-bottom")


def fail(message: str) -> None:
    print(f"FAIL: {message}", file=sys.stderr)
    raise SystemExit(1)


def mysql_escaped(value: str) -> str:
    return (
        value.replace(r"\0", "\0")
        .replace(r"\n", "\n")
        .replace(r"\r", "\r")
        .replace(r"\t", "\t")
        .replace(r"\'", "'")
        .replace(r"\"", '"')
        .replace(r"\\", "\\")
    )


def normalize(value: str) -> str:
    text = unescape(value)
    text = re.sub(r"<[^>]+>", " ", text)
    text = re.sub(r"\s+", " ", text)
    return text.casefold()


def has_all(text: str, terms: tuple[str, ...]) -> bool:
    return all(term.casefold() in text for term in terms)


def module_content_by_position() -> dict[str, str]:
    if not MYSQL.is_file():
        fail(f"missing mysql client {MYSQL}")

    quoted_positions = ",".join(f"'{position}'" for position in POSITIONS)
    query = (
        "SELECT position, title, COALESCE(content, '') "
        "FROM pnn_modules "
        f"WHERE published = 1 AND position IN ({quoted_positions}) "
        "ORDER BY FIELD(position, " + quoted_positions + "), ordering, id"
    )
    output = subprocess.check_output(
        [
            str(MYSQL),
            "-h127.0.0.1",
            "-uroot",
            "--batch",
            "--skip-column-names",
            "pn_natuna_rebuild",
            "-e",
            query,
        ],
        cwd=ROOT,
        text=True,
        encoding="utf-8",
    )

    modules = {position: "" for position in POSITIONS}
    for line in output.splitlines():
        parts = line.split("\t", 2)
        if len(parts) != 3:
            continue
        position, title, content = (mysql_escaped(part) for part in parts)
        modules.setdefault(position, "")
        modules[position] += f"\n{title}\n{content}"

    missing_positions = [position for position in POSITIONS if not modules.get(position).strip()]
    if missing_positions:
        fail("missing published footer module positions: " + ", ".join(missing_positions))

    return modules


def media_block_mentions(css: str, selector: str) -> bool:
    for match in re.finditer(r"@media[^{}]*\{", css, re.IGNORECASE):
        depth = 1
        index = match.end()
        while index < len(css) and depth:
            if css[index] == "{":
                depth += 1
            elif css[index] == "}":
                depth -= 1
            index += 1
        if selector in css[match.start():index]:
            return True
    return False


def main() -> None:
    modules = module_content_by_position()
    css = CSS.read_text(encoding="utf-8")

    raw = {position: modules[position].casefold() for position in POSITIONS}
    text = {position: normalize(modules[position]) for position in POSITIONS}
    all_footer = "\n".join(modules.values())
    all_footer_text = normalize(all_footer)
    css_lc = css.casefold()

    missing: list[str] = []

    core_service_terms = ("SIPP", "e-Court", "PTSP")
    missing_services = [term for term in core_service_terms if term.casefold() not in all_footer_text]
    if missing_services:
        missing.append("core quick links missing " + ", ".join(missing_services))
    contact_raw = raw["footer-contact"]
    contact_text = text["footer-contact"]
    required_address_terms = (
        "jalan batu sisir",
        "desa sungai ulu",
        "kecamatan bunguran timur",
        "kabupaten natuna",
        "provinsi kepulauan riau",
    )
    if not has_all(contact_text, required_address_terms):
        missing.append("footer-contact must show office address only")
    if "<address" not in contact_raw or "footer-brand-text" not in contact_raw:
        missing.append("footer address must live inside footer-brand-text address element")
    forbidden_contact_terms = ("tel:", "mailto:", "google.com/maps", "maps.app.goo.gl", "08.00", "lokasi", "email")
    forbidden_present = [term for term in forbidden_contact_terms if term in contact_raw]
    if forbidden_present:
        missing.append("footer-contact still contains removed contact items " + ", ".join(forbidden_present))

    compact_marker_present = "footer redesign" in css_lc and "compact" in css_lc
    stats_requirement_absent = ".footer-stat-chip" not in css_lc and ".footer-stats" not in css_lc
    if not compact_marker_present and not stats_requirement_absent:
        missing.append("CSS still lacks compact footer marker or stats-free footer CSS")

    bottom_text = text["footer-bottom"]
    if "© 2026 pengadilan negeri natuna kelas ii" not in bottom_text:
        missing.append("footer-bottom missing full copyright")
    forbidden_bottom_terms = ("ptip", "peta situs", "aksesibilitas")
    forbidden_bottom_present = [term for term in forbidden_bottom_terms if term in bottom_text]
    if forbidden_bottom_present:
        missing.append("footer-bottom still contains removed items " + ", ".join(forbidden_bottom_present))
    if ":focus-visible" not in css_lc:
        missing.append("CSS missing focus-visible rules")
    if not media_block_mentions(css, ".site-footer"):
        missing.append("CSS missing responsive/mobile .site-footer rules")

    if missing:
        fail("footer redesign contracts missing: " + "; ".join(missing))

    print("OK: footer homepage redesign contracts present")


if __name__ == "__main__":
    main()
