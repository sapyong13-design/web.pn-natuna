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

## Review remediation
RED command:
```text
python tools/test_import_ampuh_checklist.py
```
RED output:
```text
Ran 9 tests in 0.079s
FAILED (errors=1)
```
The new explicit-override test failed because `parse_workbook()` did not accept a keyed override input.

GREEN command:
```text
python tools/test_import_ampuh_checklist.py
```
GREEN output:
```text
Ran 9 tests in 0.081s

OK
```

Generation command:
```text
python tools/import-ampuh-checklist.py "C:/Users/faris/Downloads/ampuh-checklist-2026-merged (1).xlsx" --output templates/pn_natuna_2026/data/ampuh-2026.json
```
Output:
```text
warning: Checklist 24: blank title; using fallback
82 checklists, 405 sub-checklists, 2043 files
```
Exit code: `0`.

`tools/ampuh-2026-overrides.json` is a tracked, explicitly keyed `78.3` override. It records `source: google_drive`, `verified_at: 2026-07-13`, the Drive source URL, all 40 public filenames, and each source `folder_path` provenance. Override replaces only `78.3`, uses its verified Drive URL, and sets document count to 40. General declared-count mismatch validation remains strict; the test fixture proves an undeclared mismatch exits the CLI nonzero without creating JSON.

Corrected public inventory is 2043 files: original 2008 less three non-file status cells (`sudah terisi`, `sudah ditindaklanjuti`, `KOSONG`) plus 38 newly verified PDFs replacing the two Detail File names for 78.3. Main and checklist 78 Drive URLs are populated; other URL fields remain empty.

## Self-review
- Override is data-driven, exact-keyed, UTF-8, and preserves Drive provenance without prepending folder paths to public filenames.
- Strict 1..82 validation is unconditional.
- No Drive links beyond the approved main, checklist 78, and sub-checklist 78.3 links were scraped or added.

## Concerns
- Real workbook checklist 24 has no L1 title, so importer uses required stable fallback `Checklist 24` and emits warning.

## Commits
- `3470637` initial importer implementation.
- Review remediation commit pending.
