"""Deterministic end-to-end data contract for AMPUH 2026 directory."""
from __future__ import annotations

import json
from urllib.parse import urlparse
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATASET = ROOT / "templates" / "pn_natuna_2026" / "data" / "ampuh-2026.json"
CHECKLIST_LINKS = ROOT / "tools" / "ampuh-2026-checklist-links.json"
MAIN_DRIVE_URL = "https://drive.google.com/drive/folders/1x6yBB_YxHRKGsuxgkN1enrWXiV3P2NWH?usp=sharing"
SUBCHECKLIST_78_3_DRIVE_URL = "https://drive.google.com/drive/folders/1e18t5dXE7CRq6JR6GpoFBHflokooffmq?usp=sharing"


def is_public_drive_url(value: str) -> bool:
    parsed = urlparse(value)
    path_parts = parsed.path.split("/")
    return (
        parsed.scheme == "https"
        and parsed.netloc == "drive.google.com"
        and path_parts[:3] == ["", "drive", "folders"]
        and len(path_parts) == 4
        and bool(path_parts[3])
        and path_parts[3].replace("-", "").replace("_", "").isalnum()
    )


def main() -> None:
    directory = json.loads(DATASET.read_text(encoding="utf-8"))
    checklist_links = {int(number): url for number, url in json.loads(CHECKLIST_LINKS.read_text(encoding="utf-8")).items()}
    gobis = directory["gobis"]
    checklists = [checklist for gobi in gobis for checklist in gobi["checklists"]]
    subchecklists = [sub for checklist in checklists for sub in checklist["subchecklists"]]
    unique_checklists = {checklist["number"] for checklist in checklists}
    summary = {
        "gobis": len(gobis),
        "checklists": len(unique_checklists),
        "subchecklists": len(subchecklists),
        "documents": sum(len(sub["files"]) for sub in subchecklists),
    }

    checklist_numbers = [checklist["number"] for checklist in checklists]
    assert unique_checklists == set(range(1, 83))
    assert [gobi["number"] for gobi in gobis] == list(range(1, 28))
    assert len(subchecklists) == len({sub["number"] for sub in subchecklists})
    assert all(
        sub["number"].startswith(f'{checklist["number"]}.')
        for checklist in checklists
        for sub in checklist["subchecklists"]
    )
    assert summary == {"gobis": 27, "checklists": 82, "subchecklists": 405, "documents": 2043}
    assert [sub["number"] for checklist in gobis[2]["checklists"] if checklist["number"] == 6 for sub in checklist["subchecklists"]] == ["6.5"]
    assert [sub["number"] for checklist in gobis[10]["checklists"] if checklist["number"] == 31 for sub in checklist["subchecklists"]] == ["31.4", "31.5"]
    assert [sub["number"] for checklist in gobis[17]["checklists"] if checklist["number"] == 44 for sub in checklist["subchecklists"]] == ["44.4", "44.5", "44.6", "44.7", "44.8"]
    assert all(
        sub["document_count"] == len(sub["files"])
        for sub in subchecklists
    )

    checklist_78 = next(checklist for checklist in checklists if checklist["number"] == 78)
    subchecklist_78_3 = next(sub for sub in checklist_78["subchecklists"] if sub["number"] == "78.3")
    assert is_public_drive_url(directory["main_drive_url"])
    assert all(is_public_drive_url(checklist["drive_url"]) for checklist in checklists)
    assert subchecklist_78_3["drive_url"] == SUBCHECKLIST_78_3_DRIVE_URL
    assert directory["main_drive_url"] == MAIN_DRIVE_URL
    assert checklist_links.keys() == set(range(1, 83))
    assert {checklist["number"]: checklist["drive_url"] for checklist in checklists} == checklist_links
    assert all(is_public_drive_url(url) for url in checklist_links.values())
    assert len(subchecklist_78_3["files"]) == 40
    assert all(
        not sub["drive_url"]
        for sub in subchecklists
        if sub["number"] != "78.3"
    )
    assert not is_public_drive_url("http://drive.google.com/drive/folders/public")
    assert not is_public_drive_url("https://drive.google.com/")
    assert not is_public_drive_url("https://drive.google.com/file/d/public/view")
    assert not is_public_drive_url("https://drive.google.com/drive/folders/public/edit")
    assert not is_public_drive_url("https://evil.example/drive/folders/public")
    print("AMPUH directory E2E dataset contract: 27 GOBI, 82 unique checklists, 405 sub-checklists, 2043 documents")


if __name__ == "__main__":
    main()
