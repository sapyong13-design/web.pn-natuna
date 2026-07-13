from __future__ import annotations

import importlib.util
import tempfile
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
        ["GOBI Alpha", "1", "Layanan", "1", "Persyaratan", "2", "📁 ARSIP\nBukti A.pdf\n(KOSONG)\n… (+2 baris nama file lainnya) → lihat sheet 'Detail File'"],
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


if __name__ == "__main__":
    unittest.main(verbosity=2)
