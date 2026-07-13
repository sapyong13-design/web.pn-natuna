# AMPUH Search and Motion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver reliable AMPUH search, clearer hierarchy, and restrained accessible motion.

**Architecture:** Extend existing server-rendered AMPUH hooks and dependency-free `setupAmpuhDirectory()`. Keep dataset and global site utilities unchanged; use existing global back-to-top control.

**Tech Stack:** Joomla PHP renderer, vanilla JavaScript, CSS, PHP/Python focused contracts, Chromium QA.

## Global Constraints

- No new dependency.
- Search counts documents only.
- Invalid or absent sub-checklist URLs stay silent.
- Motion only uses opacity, transform, and token color changes.
- `prefers-reduced-motion: reduce` disables motion.

---

### Task 1: Search behavior and result UI

**Files:**
- Modify: `templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php`
- Modify: `templates/pn_natuna_2026/js/template.js`
- Test: `tools/test_ampuh_directory_interactions.py`
- Test: `tools/test_ampuh_directory_renderer.php`

**Interfaces:**
- Consumes: `[data-ampuh-search]`, `[data-ampuh-file-result]`, `[data-ampuh-gobi]`.
- Produces: `[data-ampuh-clear-search]`, `.ampuh-directory__match`, status text with document/GOBI counts.

- [ ] Add failing fixture assertions for clear control, reset after no-match, GOBI count, highlight creation/restoration, and valid conditional Drive action.
- [ ] Run focused interaction and renderer tests; confirm expected failures.
- [ ] Implement clear/reset, count aggregation, and safe text-node highlighting without changing source dataset text.
- [ ] Run focused tests; expect both contracts `ok`.

### Task 2: Visual hierarchy, sticky tools, document rows, and motion

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css`
- Test: `tools/test_ampuh_directory_renderer.php`

**Interfaces:**
- Consumes: current AMPUH BEM classes and global `.back-to-top`.
- Produces: sticky tool surface, active disclosure states, refined file rows, match mark, reduced-motion override.

- [ ] Add failing CSS assertions for sticky tools, mark styles, file focus, active hierarchy, transform/opacity transitions, and reduced-motion safeguards.
- [ ] Run renderer contract; confirm expected failures.
- [ ] Implement token-based responsive styles and 180–220 ms motion.
- [ ] Run renderer contract; expect `ok`.

### Task 3: End-to-end verification

**Files:**
- Test: `tools/test_ampuh_directory_e2e.py`

- [ ] Run interaction, renderer, and dataset E2E contracts.
- [ ] Browser-test desktop/mobile, dark mode, reduced motion, matching/no-match/reset, sticky toolbar, and global back-to-top placement.
- [ ] Remove runtime screenshots and update active handoff facts only if behavior contracts changed.
