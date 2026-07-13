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
- `cc3b4f2` review remediation: strict counts and verified 78.3 Drive override.

## GOBI label remediation
RED command: `python tools/test_import_ampuh_checklist.py`

RED output: `test_normalizes_numeric_gobi_name_but_preserves_meaningful_name ... FAIL` with `AssertionError: '1.0' != ''`.

GREEN command: `python tools/test_import_ampuh_checklist.py`

GREEN output: `Ran 10 tests in 0.081s` and `OK`.

Regeneration command: `python tools/import-ampuh-checklist.py "C:/Users/faris/Downloads/ampuh-checklist-2026-merged (1).xlsx" --output templates/pn_natuna_2026/data/ampuh-2026.json`

Regeneration output: `82 checklists, 405 sub-checklists, 2043 files` (plus required Checklist 24 fallback warning). Numeric-equivalent GOBI labels now normalize to empty strings; meaningful labels remain unchanged. Renderer contract attempted with `php tools/test_ampuh_directory_renderer.php`, but `php` is unavailable in this environment (`error: command not found: php`).

## Numeric GOBI contamination remediation
RED command: `python tools/test_import_ampuh_checklist.py`

RED output: `test_normalizes_any_numeric_gobi_name_but_preserves_meaningful_name ... FAIL` with `AssertionError: '4.0' != ''`.

GREEN command: `python tools/test_import_ampuh_checklist.py`

GREEN output: `Ran 10 tests in 0.076s` and `OK`.

Generation command completed with required warning and `82 checklists, 405 sub-checklists, 2043 files`. Any numeric-equivalent GOBI name is now empty, including a contaminated `4.0` value on current group 3; meaningful text remains unchanged. Renderer contract attempted with `php tools/test_ampuh_directory_renderer.php`; environment result: `error: command not found: php`.

## Checklist Drive link map remediation
RED command: `python tools/test_import_ampuh_checklist.py`

RED output: `Ran 12 tests in 0.084s`, with two errors: missing `validate_checklist_links` and unsupported checklist-link argument to `build_dataset`.

GREEN command: `python tools/test_import_ampuh_checklist.py`

GREEN output: `Ran 12 tests in 0.088s` and `OK`.

Generation output: required fallback warning plus `82 checklists, 405 sub-checklists, 2043 files`. Tracked `tools/ampuh-2026-checklist-links.json` is exact 1..82 and importer rejects any missing/out-of-range mapping. Every checklist receives its verified URL. Sub-checklist URLs remain empty unless explicitly overridden; 78.3 provenance override remains unchanged.

Renderer contract attempted: `php tools/test_ampuh_directory_renderer.php` returned `error: command not found: php`. E2E interaction contract: `python tools/test_ampuh_directory_interactions.py` returned `AMPUH directory interaction contract: ok`.
