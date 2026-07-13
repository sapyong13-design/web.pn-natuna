# Task 3 report

Status: complete

Files:
- `templates/pn_natuna_2026/js/template.js`
- `tools/test_ampuh_directory_interactions.py`

RED evidence:
```text
AssertionError: setupAmpuhDirectory()
```
Command: `python tools/test_ampuh_directory_interactions.py`

GREEN evidence:
```text
AMPUH directory interaction contract: ok
```
Command: `python tools/test_ampuh_directory_interactions.py`

Self-review:
- Safe no-op when AMPUH root is absent.
- Toggle and close-all keep `aria-expanded` and controlled panel `hidden` synchronized.
- Indonesian locale case-insensitive search, GOBI/search intersection, ancestor opening, live status, empty state, and empty-search all-closed reset covered by executable DOM fixture.
- No browser storage or URL mutation.
Commit: `8c42d09`
Concerns: contract uses a dependency-free Node DOM fixture, so it validates required event/state behavior but not browser layout or CSS rendering.

Commit: pending
