# Cinematic Homepage Hero Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the old courthouse backdrop and implement the ten approved cinematic hero improvements.

**Architecture:** Keep the existing Joomla PHP carousel and dynamic news data. Extend its semantic markup with an institutional label, live information ribbon, two-level action hierarchy, and editorial caption; add isolated CSS treatment and reuse existing service-hours and carousel JavaScript.

**Tech Stack:** Joomla PHP template, HTML, CSS, existing vanilla JavaScript carousel.

## Global Constraints

- Use `/images/hero/gedung-pn-natuna-2026.png`.
- Preserve existing dynamic news slide and carousel controls.
- No new libraries, backdrop blur, or continuous JavaScript animation.
- Support reduced motion and 1920/1440/768/390 px.

---

### Task 1: Cinematic hero

**Files:**
- Modify: `templates/pn_natuna_2026/hero-slider.php`
- Modify: `templates/pn_natuna_2026/css/template.css`
- Reuse: `templates/pn_natuna_2026/js/template.js`
- Add: `images/hero/gedung-pn-natuna-2026.png`

- [ ] Capture baseline proving old image and missing cinematic elements.
- [ ] Replace backdrop source and declare dimensions.
- [ ] Add institutional label, stronger title, live service ribbon, two primary actions, subordinate links, and location caption.
- [ ] Add cinematic grade, vignette, grain, asymmetric composition, push-in, light sweep, responsive focal positions, and reduced-motion handling.
- [ ] Verify image source, semantics, desktop/mobile layouts, dark mode, motion preferences, carousel news slide, controls, and overflow.
- [ ] Commit implementation.
