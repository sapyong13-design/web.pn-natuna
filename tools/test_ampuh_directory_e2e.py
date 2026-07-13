"""Deterministic end-to-end data contract for AMPUH 2026 directory."""
from __future__ import annotations

import json
from urllib.parse import urlparse
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATASET = ROOT / "templates" / "pn_natuna_2026" / "data" / "ampuh-2026.json"
MAIN_DRIVE_URL = "https://drive.google.com/drive/folders/1x6yBB_YxHRKGsuxgkN1enrWXiV3P2NWH?usp=sharing"
SUBCHECKLIST_78_3_DRIVE_URL = "https://drive.google.com/drive/folders/1e18t5dXE7CRq6JR6GpoFBHflokooffmq?usp=sharing"


def is_public_drive_url(value: str) -> bool:
    parsed = urlparse(value)
    return parsed.scheme == "https" and parsed.netloc == "drive.google.com" and bool(parsed.path)


def main() -> None:
    directory = json.loads(DATASET.read_text(encoding="utf-8"))
    gobis = directory["gobis"]
    checklists = [checklist for gobi in gobis for checklist in gobi["checklists"]]
    subchecklists = [sub for checklist in checklists for sub in checklist["subchecklists"]]
    summary = {
        "gobis": len(gobis),
        "checklists": len(checklists),
        "subchecklists": len(subchecklists),
        "documents": sum(len(sub["files"]) for sub in subchecklists),
    }

    checklist_numbers = [checklist["number"] for checklist in checklists]
    assert checklist_numbers == list(range(1, 83))
    assert all(
        sub["number"].startswith(f'{checklist["number"]}.')
        for checklist in checklists
        for sub in checklist["subchecklists"]
    )
    assert summary == {"gobis": 24, "checklists": 82, "subchecklists": 405, "documents": 2043}
    assert all(
        sub["document_count"] == len(sub["files"])
        for sub in subchecklists
    )

    checklist_78 = next(checklist for checklist in checklists if checklist["number"] == 78)
    subchecklist_78_3 = next(sub for sub in checklist_78["subchecklists"] if sub["number"] == "78.3")
    assert is_public_drive_url(directory["main_drive_url"])
    assert all(is_public_drive_url(checklist["drive_url"]) for checklist in checklists)
    assert subchecklist_78_3["drive_url"] == SUBCHECKLIST_78_3_DRIVE_URL
    assert len(subchecklist_78_3["files"]) == 40
    assert all(
        not sub["drive_url"]
        for sub in subchecklists
        if sub["number"] != "78.3"
    )
    print("AMPUH directory E2E dataset contract: 24 GOBI, 82 checklists, 405 sub-checklists, 2043 documents")


if __name__ == "__main__":
    main()
