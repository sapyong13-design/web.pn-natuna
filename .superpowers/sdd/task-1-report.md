# Task 1 report — AMPUH 2026 importer

## Status
DONE

## Files
- `tools/import-ampuh-checklist.py`
- `tools/test_import_ampuh_checklist.py`
- `templates/pn_natuna_2026/data/ampuh-2026.json`

## RED evidence
Command:
```text
python tools/test_import_ampuh_checklist.py
```
Output:
```text
FileNotFoundError: [Errno 2] No such file or directory: 'C:\\tmp\\web.pn-natuna\\.worktrees\\ampuh-directory\\tools\\import-ampuh-checklist.py'
```
Exit code: `1`.

## GREEN evidence
Command:
```text
python tools/test_import_ampuh_checklist.py
```
Output:
```text
Ran 6 tests in 0.011s

OK
```
Exit code: `0`.

## Generation evidence
Command:
```text
python tools/import-ampuh-checklist.py "C:/Users/faris/Downloads/ampuh-checklist-2026-merged (1).xlsx" --output templates/pn_natuna_2026/data/ampuh-2026.json
```
Output:
```text
warning: Checklist 24: blank title; using fallback
82 checklists, 405 sub-checklists, 2008 files
```
Exit code: `0`.

Repeated generation SHA-256:
```text
137f4a95ca471ec492a4133ba4e3c65d91a4f4f0faad05774da372b2672c1497
```
Both generated artifacts had this exact hash. Dataset contract smoke test confirmed exact checklist numbers `1..82`, empty main/checklist/sub-checklist Drive URLs, and `document_count == len(files)` for every sub-checklist.

## Self-review
- Parser uses only `zipfile` and `xml.etree.ElementTree`; it reads OOXML values only and never executes macros or formulas.
- Tests cover merged-parent forward fill, compound sub-numbers, GOBI ownership at checklist granularity, blank-title fallback, truncation replacement from Detail File, folder-tree filtering, and duplicate-number validation.
- Generated JSON has canonical public top-level keys and deterministic UTF-8 serialization.

## Concerns
- Real workbook checklist 24 has no L1 title. Importer emitted stable `Checklist 24` fallback and warning as required.

## Commit
3470637
