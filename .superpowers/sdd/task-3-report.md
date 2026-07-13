# Task 3 report

Status: complete

Files:
- `templates/pn_natuna_2026/js/template.js`
- `tools/test_ampuh_directory_interactions.py`

RED evidence:
```text
AssertionError: setupAmpuhDirectory()
Error: sub-checklist title search must produce one countable result
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
- Indonesian locale case-insensitive search, GOBI/search intersection, ancestor opening, live status, empty state, explicit countable sub-checklist/file result nodes, and empty-search all-closed reset covered by executable DOM fixture.
- Empty-query restoration removes the tree empty-state class.
- No browser storage or URL mutation.
Renderer contract: attempted `php tools/test_ampuh_directory_renderer.php`; blocked because `php` executable is unavailable in this worktree environment (`command not found: php`).
Commit: `e99d195`
