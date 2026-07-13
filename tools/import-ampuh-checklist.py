from __future__ import annotations

import argparse
import json
import re
import sys
import zipfile
import xml.etree.ElementTree as ET
from collections import defaultdict
from pathlib import Path

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main", "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships"}
NON_FILE_STATUS = {"kosong", "sudah terisi", "sudah ditindaklanjuti"}
REL_NS = "http://schemas.openxmlformats.org/package/2006/relationships"
TRUNCATED = re.compile(r"^… \(\+[0-9]+ baris nama file lainnya\) → lihat sheet 'Detail File'$")
WARNINGS: list[str] = []
DEFAULT_OVERRIDES = Path(__file__).with_name("ampuh-2026-overrides.json")
MAIN_DRIVE_URL = "https://drive.google.com/drive/folders/1x6yBB_YxHRKGsuxgkN1enrWXiV3P2NWH?usp=sharing"
CHECKLIST_DRIVE_URLS = {78: "https://drive.google.com/drive/folders/12aqCl7P5I0Gg97p4Ch9IGZtMga93d62o?usp=sharing"}


def text(value: object) -> str:
    return str(value or "").strip()


def number(value: object) -> int | None:
    value = text(value)
    try:
        parsed = float(value)
        return int(parsed) if parsed.is_integer() else None
    except ValueError:
        return None


def column_index(reference: str) -> int:
    letters = re.match(r"[A-Z]+", reference).group(0)
    value = 0
    for letter in letters:
        value = value * 26 + ord(letter) - 64
    return value - 1


def load_sheets(archive: zipfile.ZipFile) -> dict[str, str]:
    workbook = ET.fromstring(archive.read("xl/workbook.xml"))
    rels = ET.fromstring(archive.read("xl/_rels/workbook.xml.rels"))
    targets = {rel.attrib["Id"]: rel.attrib["Target"] for rel in rels.findall(f"{{{REL_NS}}}Relationship")}
    return {sheet.attrib["name"]: "xl/" + targets[sheet.attrib[f"{{{NS['r']}}}id"]].lstrip("/") for sheet in workbook.findall(".//m:sheet", NS)}


def shared_strings(archive: zipfile.ZipFile) -> list[str]:
    if "xl/sharedStrings.xml" not in archive.namelist():
        return []
    root = ET.fromstring(archive.read("xl/sharedStrings.xml"))
    return ["".join(item.itertext()) for item in root.findall("m:si", NS)]


def read_rows(archive: zipfile.ZipFile, sheet_path: str) -> list[list[str]]:
    strings = shared_strings(archive)
    root = ET.fromstring(archive.read(sheet_path))
    rows: list[list[str]] = []
    for row in root.findall(".//m:sheetData/m:row", NS):
        cells: dict[int, str] = {}
        for cell in row.findall("m:c", NS):
            raw = cell.findtext("m:v", default="", namespaces=NS)
            if cell.attrib.get("t") == "s" and raw:
                value = strings[int(raw)]
            elif cell.attrib.get("t") == "inlineStr":
                value = "".join(cell.find("m:is", NS).itertext())
            else:
                value = raw
            cells[column_index(cell.attrib["r"])] = text(value)
        if cells:
            rows.append([cells.get(index, "") for index in range(max(cells) + 1)])
    return rows


def row_value(row: list[str], index: int) -> str:
    return row[index] if index < len(row) else ""


def normalized(value: str) -> str:
    return " ".join(text(value).split()).casefold()


def files_from(value: str) -> list[str]:
    return [line.strip().lstrip("- ").strip() for line in value.splitlines() if line.strip() and normalized(line.strip().lstrip("- ").strip()) not in NON_FILE_STATUS and not line.strip().startswith("📁") and line.strip() != "(KOSONG)" and not TRUNCATED.fullmatch(line.strip())]


def detail_files(details: list[list[str]]) -> tuple[dict[tuple[int, str], list[str]], dict[int, list[tuple[str, list[str]]]]]: 
    result: dict[tuple[int, str], list[str]] = defaultdict(list)
    ordered: dict[int, list[tuple[str, list[str]]]] = defaultdict(list)
    for row in details:
        folder, title, filename = number(row_value(row, 1)), text(row_value(row, 2)), text(row_value(row, 4))
        if folder is None or not title or not filename or title == "Kriteria (Checklist)":
            continue
        key = (folder, normalized(title))
        if key not in result:
            ordered[folder].append((normalized(title), result[key]))
        result[key].extend(files_from(filename))
    return result, ordered



def load_overrides(path: Path | None) -> dict[str, dict]:
    if path is None or not path.exists():
        return {}
    return json.loads(path.read_text(encoding="utf-8"))

def gobi_name(value: str, group_number: int) -> str:
    return "" if number(value) == group_number else value
def build_dataset(rows: list[list[str]], details: list[list[str]], overrides: dict[str, dict] | None = None) -> dict:
    global WARNINGS
    WARNINGS = []
    overrides = overrides or {}
    detail_index, detail_order = detail_files(details)
    pending_gobi = "Tidak Ditentukan"
    checklists: list[dict] = []
    current: dict | None = None
    last_number: int | None = None
    for row in rows:
        gobi, raw_number, raw_title, raw_sub, sub_title, raw_count, raw_files = (row_value(row, i) for i in range(7))
        candidate_number = number(raw_number)
        if gobi and gobi != "GOBI":
            pending_gobi = gobi
        if candidate_number is not None and candidate_number != last_number:
            title = text(raw_title) or f"Checklist {candidate_number}"
            if not text(raw_title):
                WARNINGS.append(f"Checklist {candidate_number}: blank title; using fallback")
            current = {"number": candidate_number, "title": title, "drive_url": "", "subchecklists": [], "_gobi": pending_gobi}
            checklists.append(current)
            last_number = candidate_number
        if current is None:
            continue
        sub_number = number(raw_sub)
        if sub_number is None or not text(sub_title):
            continue
        listed = files_from(raw_files)
        key = (current["number"], normalized(sub_title))
        authoritative = detail_index.get(key)
        if any(TRUNCATED.fullmatch(line.strip()) for line in raw_files.splitlines()):
            if authoritative is None:
                candidates = detail_order.get(current["number"], [])
                if sub_number <= len(candidates):
                    authoritative = candidates[sub_number - 1][1]
            listed = authoritative or []
        override = overrides.get(f"{current['number']}.{sub_number}")
        if override is not None:
            listed = [text(item.get("name")) for item in override.get("files", [])]
            declared = len(listed)
            drive_url = text(override.get("drive_url"))
        else:
            declared = number(raw_count)
            drive_url = ""
        current["subchecklists"].append({"number": f"{current['number']}.{sub_number}", "title": text(sub_title), "document_count": declared if declared is not None else len(listed), "drive_url": drive_url, "files": listed})
    gobis: dict[str, list[dict]] = defaultdict(list)
    for checklist in checklists:
        gobis[checklist.pop("_gobi")].append(checklist)
    return {"title": "AMPUH 2026 Checklist", "main_drive_url": MAIN_DRIVE_URL, "summary": "Daftar checklist dan bukti fisik dokumen AMPUH 2026 Pengadilan Negeri Natuna.", "gobis": [{"number": index, "name": gobi_name(name, index), "checklists": [{**checklist, "drive_url": CHECKLIST_DRIVE_URLS.get(checklist["number"], "")} for checklist in items]} for index, (name, items) in enumerate(gobis.items(), start=1)]}


def parse_workbook(path: Path, override_path: Path | None = DEFAULT_OVERRIDES) -> dict:
    with zipfile.ZipFile(path) as archive:
        sheets = load_sheets(archive)
        return build_dataset(read_rows(archive, sheets["AMPUH 2026 Checklist"]), read_rows(archive, sheets["Detail File"]), load_overrides(override_path))


def validate_dataset(data: dict) -> list[str]:
    errors: list[str] = []
    checklists = [checklist for gobi in data.get("gobis", []) for checklist in gobi.get("checklists", [])]
    numbers = [item.get("number") for item in checklists]
    if len(numbers) != len(set(numbers)):
        errors.append("Duplicate checklist number")
    if numbers != list(range(1, 83)):
        errors.append("Checklist numbers must be exactly 1..82")
    for checklist in checklists:
        if not text(checklist.get("title")):
            errors.append(f"Checklist {checklist.get('number')}: blank title")
        for index, sub in enumerate(checklist.get("subchecklists", []), start=1):
            if sub.get("number") != f"{checklist.get('number')}.{index}":
                errors.append(f"Checklist {checklist.get('number')}: invalid sub-number {sub.get('number')}")
            if sub.get("document_count") != len(sub.get("files", [])):
                errors.append(f"Checklist {checklist.get('number')} sub {sub.get('number')}: document count mismatch")
            if any(file.startswith("📁") or file == "(KOSONG)" for file in sub.get("files", [])):
                errors.append(f"Checklist {checklist.get('number')} sub {sub.get('number')}: invalid filename")
    return errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("workbook", type=Path)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--overrides", type=Path, default=DEFAULT_OVERRIDES)
    args = parser.parse_args()
    data = parse_workbook(args.workbook, args.overrides)
    errors = validate_dataset(data)
    if errors:
        print("\n".join(errors), file=sys.stderr)
        return 1
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    for warning in WARNINGS:
        print(f"warning: {warning}", file=sys.stderr)
    subs = sum(len(checklist["subchecklists"]) for gobi in data["gobis"] for checklist in gobi["checklists"])
    files = sum(len(sub["files"]) for gobi in data["gobis"] for checklist in gobi["checklists"] for sub in checklist["subchecklists"])
    print(f"{len([c for g in data['gobis'] for c in g['checklists']])} checklists, {subs} sub-checklists, {files} files")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
