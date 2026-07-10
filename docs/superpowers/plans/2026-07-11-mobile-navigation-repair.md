# Mobile Navigation Repair Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace broken mobile menu with compact accessible right drawer and verify compatibility across mobile widths, landscape, text scaling, dark mode, reduced motion, and 760/761px boundary.

**Architecture:** Keep Joomla menu output and enhance it client-side: each parent link receives a sibling expansion button controlling its existing child `<ul>`. Replace conflicting mobile drawer CSS at source, then extend existing menu setup with accordion state, focus trap, scroll lock, restoration, and fixed-control suppression. Desktop markup and behavior remain unchanged.

**Tech Stack:** Joomla PHP template, semantic HTML, CSS, vanilla JavaScript, Chromium browser QA.

## Global Constraints

- No new dependency or database menu restructuring.
- Mobile rules apply at viewport widths up to 760px; desktop at 761px and above remains unchanged.
- Drawer width is `min(90vw, 360px)` and height uses `100dvh` with `100vh` fallback.
- Touch targets are at least 44×44px.
- Homepage bottom navigation remains homepage-only with the existing five destinations.
- Support three menu levels, safe areas, landscape, 200% text zoom, dark mode, reduced motion, keyboard, and screen readers.

---

### Task 1: Drawer Semantics and Identity

**Files:**
- Modify: `templates/pn_natuna_2026/index.php:109-122`

**Interfaces:**
- Produces: `#main-menu-list[aria-modal]`, `.mobile-menu-brand`, `.mobile-menu-scroll`, `.mobile-menu-footer`; existing `.menu-toggle`, `.menu-close`, `.dark-toggle` remain stable JS hooks.

- [ ] Capture failing browser assertions: panel lacks dialog semantics/compact identity and its navigation list is not inside a dedicated scroll region.
- [ ] Run assertions at 390×844 and confirm failure.
- [ ] Add compact logo/name in drawer header; give panel `role="dialog"`, `aria-modal="true"`, `aria-labelledby`; wrap Joomla output in `.mobile-menu-scroll`; move dark toggle into `.mobile-menu-footer`.
- [ ] Reload and verify semantics and identity pass without changing desktop visibility.
- [ ] Commit `feat: structure accessible mobile navigation drawer`.

### Task 2: Accordion, Focus, and Scroll State

**Files:**
- Modify: `templates/pn_natuna_2026/js/template.js:1-48`

**Interfaces:**
- Consumes: existing Joomla `li.parent > a + ul` hierarchy and Task 1 drawer hooks.
- Produces: `.submenu-toggle[aria-expanded][aria-controls]`, parent `.submenu-open`, body `.menu-drawer-open`; `setMenuOpen(open, options)` restores focus/scroll.

- [ ] Write browser assertions for generated toggle count, collapsed child lists, inline single-open accordion, current branch expansion, focus entry/cycle/return, Escape/backdrop close, body scroll preservation, and resize cleanup above 760px.
- [ ] Run and confirm failures against current code.
- [ ] Enhance each parent with a button and stable child-list ID; implement one-open-per-level accordion while retaining nested branch state.
- [ ] Extend `setMenuOpen`: remember trigger and scrollY, lock body, focus close button, trap Tab/Shift+Tab, close via Escape/backdrop/link, restore scroll/focus, reset cleanly at desktop breakpoint.
- [ ] Run behavioral assertions and confirm pass.
- [ ] Commit `feat: add accessible mobile menu interactions`.

### Task 3: Replace Broken Drawer Styling

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css` existing menu/drawer rules and mobile block `9764-9921`

**Interfaces:**
- Consumes: Task 1 shell classes and Task 2 accordion states.
- Produces: right drawer, full backdrop, fixed internal header, flowing accordion lists, active state, safe-area layout, open-state control suppression.

- [ ] Capture failing geometry assertions at 320×568, 390×844, and 844×390: panel width/height, full backdrop, row target sizes, children flow, no huge vertical gaps, competing controls visible.
- [ ] Remove or replace conflicting mobile rules that distribute nested lists using fixed/absolute geometry; do not append a third override system.
- [ ] Style cream drawer surfaces, maroon text, gold current indicator, 48px rows, immediate nested flow, scrollable `.mobile-menu-scroll`, safe-area header/footer, 44px close/chevrons, and focus-visible rings.
- [ ] Hide `.mobile-quick-actions`, `.access-panel`, `.floating-whatsapp`, and `.back-to-top` under `body.menu-drawer-open`; disable their pointer interaction.
- [ ] Add dark theme and reduced-motion variants; keep desktop declarations untouched.
- [ ] Run geometry assertions and screenshot light/dark portrait/landscape.
- [ ] Commit `style: repair mobile navigation drawer layout`.

### Task 4: Mobile Compatibility QA and Cleanup

**Files:**
- Modify only if QA exposes a source defect: `templates/pn_natuna_2026/index.php`, `templates/pn_natuna_2026/js/template.js`, `templates/pn_natuna_2026/css/template.css`
- Update: `HANDOFF.md`

**Interfaces:**
- Verifies completed public mobile navigation contract.

- [ ] Run viewport matrix: 320×568, 360×800, 390×844, 430×932, 667×375, 844×390, widths 760 and 761.
- [ ] Verify no horizontal overflow, drawer controls remain reachable, all visible targets ≥44px, nested third-level navigation works, and fixed controls never overlap drawer.
- [ ] Verify homepage plus `/transparansi` and `/profil-pengadilan/profil-kepaniteraan`; confirm accurate current branch/current link.
- [ ] Verify keyboard focus trap/return, screen-reader expanded/current states, dark mode, reduced motion, and 200% text scaling.
- [ ] Verify desktop at 761, 1440, and 1920 has unchanged menu geometry and no mobile drawer controls visible.
- [ ] Record final architecture, hooks, breakpoints, and test matrix in `HANDOFF.md`; remove superseded mobile-menu warnings.
- [ ] Commit `docs: record repaired mobile navigation behavior`.

### Task 5: Independent Review and Delivery

**Files:**
- Review all files changed by Tasks 1-4.

- [ ] Request independent code/design review against `docs/superpowers/specs/2026-07-11-mobile-navigation-repair-design.md`.
- [ ] Fix every valid functional, accessibility, responsive, and desktop-safety finding at source; rerun affected browser checks.
- [ ] Run final focused browser suite and capture evidence.
- [ ] Commit any review fixes.
- [ ] Push all commits to `origin/continue-joomla-rebuild-polish`.
