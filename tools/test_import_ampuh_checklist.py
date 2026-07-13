from __future__ import annotations

import importlib.util
import json
import tempfile
import subprocess
import sys
import unittest
import zipfile
from pathlib import Path
from xml.sax.saxutils import escape

ROOT = Path(__file__).resolve().parents[1]
IMPORTER_PATH = ROOT / "tools" / "import-ampuh-checklist.py"
SPEC = importlib.util.spec_from_file_location("ampuh_importer", IMPORTER_PATH)
assert SPEC and SPEC.loader
IMPORTER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(IMPORTER)


def sheet_xml(rows: list[list[str | None]], merges: list[str] = []) -> str:
    def cell(col: int, row: int, value: str) -> str:
        name = f"{chr(65 + col)}{row}"
        return f'<c r="{name}" t="inlineStr"><is><t>{escape(value)}</t></is></c>'

    rendered_rows = []
    for index, values in enumerate(rows, start=1):
        rendered = "".join(cell(col, index, value) for col, value in enumerate(values) if value is not None)
        rendered_rows.append(f'<row r="{index}">{rendered}</row>')
    merge_xml = "".join(f'<mergeCell ref="{item}"/>' for item in merges)
    return (
        '<?xml version="1.0" encoding="UTF-8"?>'
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        f'<sheetData>{"".join(rendered_rows)}</sheetData><mergeCells>{merge_xml}</mergeCells></worksheet>'
    )


def write_fixture(path: Path) -> None:
    checklist_rows = [
        ["GOBI", "No", "Checklist", "Sub", "Sub checklist", "Jumlah", "Nama File"],
        ["GOBI Alpha", "1", "Layanan", "1", "Persyaratan", "3", "📁 ARSIP\nBukti A.pdf\n(KOSONG)\n… (+2 baris nama file lainnya) → lihat sheet 'Detail File'"],
        [None, None, None, "2", "Publikasi", "1", "Publikasi.pdf"],
        ["GOBI Beta", None, None, None, None, None, None],
        [None, "2", None, "1", "Pengawasan", "1", "Laporan.pdf"],
    ]
    detail_rows = [
        ["No", "Folder", "Checklist", "Lokasi", "Nama File"],
        ["1", "1", "Persyaratan", "", "Bukti A.pdf\nRekap B.xlsx"],
        ["2", "1", "Publikasi", "", "Publikasi.pdf"],
        ["3", "2", "Pengawasan", "", "Laporan.pdf"],
    ]
    workbook = ('<?xml version="1.0" encoding="UTF-8"?>'
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                '<sheets><sheet name="AMPUH 2026 Checklist" sheetId="1" r:id="rId1"/>'
                '<sheet name="Detail File" sheetId="2" r:id="rId2"/></sheets></workbook>')
    relationships = ('<?xml version="1.0" encoding="UTF-8"?>'
                     '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                     '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                     '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
                     '</Relationships>')
    with zipfile.ZipFile(path, "w") as archive:
        archive.writestr("xl/workbook.xml", workbook)
        archive.writestr("xl/_rels/workbook.xml.rels", relationships)
        archive.writestr("xl/worksheets/sheet1.xml", sheet_xml(checklist_rows, ["A2:A3", "B2:B4", "C2:C4"]))
        archive.writestr("xl/worksheets/sheet2.xml", sheet_xml(detail_rows))


class AmpuhImporterTests(unittest.TestCase):
    def setUp(self) -> None:
        self.tempdir = tempfile.TemporaryDirectory()
        self.fixture = Path(self.tempdir.name) / "fixture.xlsx"
        write_fixture(self.fixture)
        self.override = Path(self.tempdir.name) / "overrides.json"
        self.override.write_text(json.dumps({"1.1": {"source": "test", "drive_url": "https://example.test/1.1", "files": [{"name": "A.pdf"}, {"name": "B.pdf"}, {"name": "C.pdf"}]}}, ensure_ascii=False), encoding="utf-8")

    def tearDown(self) -> None:
        self.tempdir.cleanup()

    def test_inherits_merged_parent_cells_and_builds_compound_numbers(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        self.assertEqual(data["gobis"][0]["checklists"][0]["number"], 1)
        self.assertEqual(
            [item["number"] for item in data["gobis"][0]["checklists"][0]["subchecklists"]],
            ["1.1", "1.2"],
        )

    def test_detail_sheet_replaces_truncated_file_summary(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        files = data["gobis"][0]["checklists"][0]["subchecklists"][0]["files"]
        self.assertEqual(files, ["Bukti A.pdf", "Rekap B.xlsx"])

    def test_assigns_gobi_once_per_checklist_not_per_row(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        checklist_one = data["gobis"][0]["checklists"][0]
        self.assertEqual([sub["number"] for sub in checklist_one["subchecklists"]], ["1.1", "1.2"])
        self.assertEqual(data["gobis"][1]["checklists"][0]["number"], 2)

    def test_uses_stable_fallback_for_blank_l1_title(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        self.assertEqual(data["gobis"][1]["checklists"][0]["title"], "Checklist 2")

    def test_folder_tree_and_empty_markers_are_not_filenames(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        files = data["gobis"][0]["checklists"][0]["subchecklists"][0]["files"]
        self.assertNotIn("📁 ARSIP", files)
        self.assertNotIn("(KOSONG)", files)

    def test_validation_rejects_missing_or_duplicate_checklist_numbers(self) -> None:
        errors = IMPORTER.validate_dataset({"gobis": [{"checklists": [
            {"number": 1, "subchecklists": []}, {"number": 1, "subchecklists": []}
        ]}]})
        self.assertTrue(any("duplicate" in error.lower() for error in errors))

    def test_validation_requires_exactly_checklist_numbers_one_through_82(self) -> None:
        checklists = [{"number": value, "title": "x", "subchecklists": []} for value in range(1, 82)]
        errors = IMPORTER.validate_dataset({"gobis": [{"checklists": checklists}]})
        self.assertTrue(any("1..82" in error for error in errors))

    def test_declared_document_count_mismatch_is_reported_and_cli_fails(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture)
        sub = data["gobis"][0]["checklists"][0]["subchecklists"][0]
        self.assertEqual(sub["document_count"], 3)
        self.assertEqual(sub["files"], ["Bukti A.pdf", "Rekap B.xlsx"])
        errors = IMPORTER.validate_dataset(data)
        self.assertTrue(any("document count mismatch" in error.lower() for error in errors))
        output = Path(self.tempdir.name) / "output.json"
        result = subprocess.run([sys.executable, str(IMPORTER_PATH), str(self.fixture), "--output", str(output)], capture_output=True, text=True)
        self.assertEqual(result.returncode, 1)
        self.assertIn("document count mismatch", result.stderr.lower())
        self.assertFalse(output.exists())

    def test_only_explicit_keyed_override_resolves_declared_count_mismatch(self) -> None:
        data = IMPORTER.parse_workbook(self.fixture, self.override)
        sub = data["gobis"][0]["checklists"][0]["subchecklists"][0]
        self.assertEqual(sub["files"], ["A.pdf", "B.pdf", "C.pdf"])
        self.assertEqual(sub["document_count"], 3)
        self.assertEqual(sub["drive_url"], "https://example.test/1.1")
        self.assertFalse(any("document count mismatch" in error.lower() for error in IMPORTER.validate_dataset(data)))

    def test_normalizes_any_numeric_gobi_name_but_preserves_meaningful_name(self) -> None:
        numeric = IMPORTER.build_dataset([["4.0", "1", "Checklist", "1", "Sub", "0", ""]], [], {})
        shifted_numeric = IMPORTER.build_dataset([["4.0", "3", "Checklist", "1", "Sub", "0", ""]], [], {})
        named = IMPORTER.build_dataset([["GOBI Alpha", "1", "Checklist", "1", "Sub", "0", ""]], [], {})
        self.assertEqual(numeric["gobis"][0]["name"], "")
        self.assertEqual(shifted_numeric["gobis"][0]["number"], 1)
        self.assertEqual(shifted_numeric["gobis"][0]["name"], "")
        self.assertEqual(named["gobis"][0]["name"], "GOBI Alpha")

    def test_checklist_link_map_requires_exact_numbers_one_through_82(self) -> None:
        with self.assertRaisesRegex(ValueError, "1..82"):
            IMPORTER.validate_checklist_links({str(value): "https://drive.google.com/folder" for value in range(1, 82)})
        links = IMPORTER.validate_checklist_links({str(value): f"https://drive.google.com/folders/{value}" for value in range(1, 83)})
        self.assertEqual(links[1], "https://drive.google.com/folders/1")
        self.assertEqual(links[82], "https://drive.google.com/folders/82")

    def test_checklist_link_map_sets_checklist_not_subchecklist_urls(self) -> None:
        links = {value: f"https://drive.google.com/folders/{value}" for value in range(1, 83)}
        data = IMPORTER.build_dataset([["GOBI", "1", "Checklist", "1", "Sub", "0", ""]], [], {}, links)
        checklist = data["gobis"][0]["checklists"][0]
        self.assertEqual(checklist["drive_url"], "https://drive.google.com/folders/1")
        self.assertEqual(checklist["subchecklists"][0]["drive_url"], "")


if __name__ == "__main__":
    unittest.main(verbosity=2)
