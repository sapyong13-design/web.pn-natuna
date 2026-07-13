# AMPUH final fix report

## Scope
- Per-GOBI and per-checklist visible inventory metadata.
- Deterministic decorative file-type markers.
- Search clearing retains active GOBI selection while closing disclosures.
- Removed unused importer `CHECKLIST_DRIVE_URLS` constant.

## RED
- `python tools/test_ampuh_directory_interactions.py` failed: `clearing search must preserve active GOBI filter and clear empty state`.
- Renderer assertions added for scoped counts, escaped decorative PDF marker, all required classifications, and compact marker styling; behavior was absent before renderer implementation.

## GREEN
- `python tools/test_import_ampuh_checklist.py` — 12 tests passed.
- `python tools/test_ampuh_directory_interactions.py` — passed.
- `python tools/test_ampuh_directory_e2e.py` — passed: 24 GOBI, 82 checklists, 405 sub-checklists, 2043 documents.
- `C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe tools/test_ampuh_directory_renderer.php` — passed.
- Regenerated with private workbook command. `git diff --exit-code -- templates/pn_natuna_2026/data/ampuh-2026.json` exited 0: tracked dataset byte-identical.

## Files
- `templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php`
- `templates/pn_natuna_2026/js/template.js`
- `templates/pn_natuna_2026/css/template.css`
- `tools/import-ampuh-checklist.py`
- `tools/test_ampuh_directory_renderer.php`
- `tools/test_ampuh_directory_interactions.py`

## Follow-up: GOBI filter pressed state
- RED: renderer contract failed `Rendered GOBI filters must expose an initial unpressed state.`
- GREEN: renderer contract, interaction contract, and E2E dataset contract passed after every rendered filter button gained `aria-pressed="false"`; interaction fixture asserts both initial buttons are false before clicks.

## Browser/curl concern
`curl -I http://127.0.0.1/ampuh` could not connect because no local HTTP server was listening. Browser live-page scenario cannot run without a server.

## Commit
Current `HEAD` — `fix: address AMPUH final review findings`
