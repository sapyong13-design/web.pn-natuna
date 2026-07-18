# Comprehensive Mobile Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans task-by-task.

**Goal:** Complete all remaining mobile audit recommendations without changing public routes or official artwork.

**Architecture:** Existing template markup gains compact status and filtering hooks. Vanilla JS owns menu filtering, rail counters, adaptive prefetch, and interaction state. CSS owns breakpoint-specific density, visibility, stable hero geometry, and accessible touch presentation.

**Tech Stack:** Joomla 5, PHP 8.3, CSS, vanilla JavaScript.

## Constraints

- No fabricated official artwork.
- No runtime dependencies.
- Mobile-only changes remain below 760px unless behavior is device-independent.
- Preserve reduced motion, lazy iframe, dark mode, and canonical routes.

### Task 1: Contract
- Create `tools/test_comprehensive_mobile_polish.php` covering every design decision.
- Run red.

### Task 2: Markup and interaction
- Modify `index.php`, `hero-slider.php`, and `template.js` for menu filter, poster CTA, rail/sidebar status, back-to-top threshold, and guarded prefetch.
- Run source contract.

### Task 3: Responsive presentation
- Modify `template.css` for hero stability, duplicate suppression, floating-control cleanup, three-video rail, rail hints, font floors, hidden scrollbars, and touch feedback.
- Run source contract.

### Task 4: Verification
- Browser QA 320/390 and 200% text zoom.
- Run focused contracts and performance contracts.
- Update `HANDOFF.md`, commit locally, do not push.