# Mobile Homepage Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a mobile-first homepage shell and content flow that makes PN Natuna services understandable within two taps.

**Architecture:** Extend existing Joomla template markup with mobile-only semantic action and intent sections. Use scoped responsive CSS and minimal existing vanilla JS hooks; preserve desktop module rendering and dynamic data.

**Tech Stack:** Joomla PHP template, semantic HTML, CSS, existing vanilla JavaScript.

## Global Constraints

- Mobile redesign applies at widths up to 760 px.
- Preserve desktop layout and existing destination URLs.
- No new JavaScript or CSS library.
- Minimum interactive target 44×44 px.
- Respect safe areas and reduced motion.

---

### Task 1: Mobile shell and content architecture

**Files:**
- Modify: `templates/pn_natuna_2026/index.php`
- Modify: `templates/pn_natuna_2026/hero-slider.php`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Modify only if needed: `templates/pn_natuna_2026/js/template.js`

- [ ] Capture current mobile baseline for header height, bottom actions, hero density, missing start-here/intent sections, sidebar stacking, and control collisions.
- [ ] Add compact mobile brand treatment and five-action bottom navigation.
- [ ] Add semantic start-here and intent disclosure sections using existing routes.
- [ ] Simplify mobile hero information and CTA hierarchy without changing desktop.
- [ ] Compact sidebar modules and progressively disclose lower content.
- [ ] Add mobile visual system, touch feedback, reduced motion, safe-area spacing, and deferred rendering.
- [ ] Verify 390/430/760/768/1440/1920, interactions, accessibility, desktop preservation, and external embed loading.
- [ ] Commit implementation.
