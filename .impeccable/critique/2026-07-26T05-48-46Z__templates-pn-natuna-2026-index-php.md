---
target: beranda page
total_score: 17
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 2
timestamp: 2026-07-26T05-48-46Z
slug: templates-pn-natuna-2026-index-php
---
# Critique — Beranda PN Natuna Kelas II

Method: dual-agent (A: AssessmentA/designer · B: AssessmentB/task). Mode: Operate. All 10 heuristics apply.

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 1 | Clock reads `Minggu, 26 Juli 2026`; schedule tab reads `Hari Ini / Kamis, 23 Jul. 2026 / 12 perkara`. Verified by parent. `sipp-schedule.php:144` parses SIPP's `updated` and never renders it. |
| 2 | Match System / Real World | 2 | `.sipp-card-case` register number is the largest text (17.92px Fraunces); the plain-language agenda is ~12px inside a chip. |
| 3 | User Control and Freedom | 3 | Hero pause + `aria-pressed`, 28 CSS / 6 JS reduced-motion guards. 42 `target="_blank"` links announce nothing. |
| 4 | Consistency and Standards | 2 | `.module-card` renders four treatments from one class. 37 font-sizes, 4 date formats, 3 arrow glyphs. Adjusted up from A's 1: palette and component language are recognisably one system. |
| 5 | Error Prevention | 1 | `.sipp-card-link` overlaps `.sipp-card-case` on 12/12 cards (19-72px x 34px, verified). All 12 links share the accessible name "Detil". |
| 6 | Recognition Rather Than Recall | 2 | Mobile right rail becomes a sideways snap carousel at y=7,831 of 8,715. |
| 7 | Flexibility and Efficiency | 2 | 15 SIPP links, zero above the fold. Mobile bottom-nav "Perkara" is a zero-scroll tap; desktop is slower for the repeat user. |
| 8 | Aesthetic and Minimalist Design | 1 | Institution name rendered 5x within the first 950px. 14 top-level blocks, 71 CTAs, 9 identical eyebrows, 3 of 4 descriptions restate their heading. |
| 9 | Error Recovery | 1 | `sipp-schedule.php:62` defines `tanggal gagal dimuat` and never renders it. Empty state offers no phone, no SIPP link, though both exist on the page. |
| 10 | Help and Documentation | 2 | Accessibility panel labelled in English under an Indonesian heading. No gloss for `Pihak: Tidak dipublikasikan`, `Pid.Sus-LH`, `prodeo`, `posbakum`. |
| **Total** | | **17/40** | **Poor — band set by the stale-data P0, not by taste** |

Holistic craft rating: **5.5/10** now, **8.3/10** projected. The gap between 17/40 and 5.5/10 is real: the craft floor (CLS 0.0099, reduced motion implemented not gestured at, 0 missing alt, sourced numbers) is well above average, while the primary task returns a wrong answer.

## Design Specificity Verdict

Category-interchangeable composition; unrepeatable content. Swap logo and maroon for blue and this layout serves a Dinas Kesehatan or a regional university unchanged: an 8-tile logo grid, a section molecule used 9 times, a 721px scrim hero, a 4-up facility grid, two donuts, an Instagram embed.

What is unrepeatably this court — `Sidang Keliling` (circuit court sailing to outer islands), `Kepaniteraan Khusus Perikanan`, courtrooms CAKRA and SARI — is rendered as 12px pills and dropdown child #4.

Deterministic scan: CLI detector exitCode 0, 0 findings on markup. In-page detector: 107 anti-patterns. The CLI-clean / browser-dirty split is explained by A's independent finding: the faults are in the rendered token system, not in the markup files.

## What's Working

1. CLS 0.0099, LCP 1,164ms, FCP 892ms — earned. Server-side clock render; topbar pinned to `height:40px;overflow:hidden` with a comment recording the 1.109 CLS regression that motivated it.
2. Reduced motion is real. 28 CSS blocks + 6 JS guards. `hero-slider.php:312` sets `aria-live="off"` during auto-rotation and flips to `polite` only on manual control, with a comment explaining why.
3. The content tells the truth. 46 images, 0 missing alt, 21 correctly-empty decorative alts. DIPA links each period to its SP2D PDF; April 2026 is absent rather than interpolated.

## Priority Issues

### [P0] Homepage states the wrong day's docket as today's
Verified: clock `Minggu, 26 Juli 2026`; `#sipp-tab-today` = `Hari Ini / Kamis, 23 Jul. 2026 / 12 perkara`; `#sipp-tab-tomorrow` = `Besok / Jumat, 24 Jul. 2026`. Section text claims "diperbarui otomatis dari SIPP". Hero ribbon prints "Agenda hari ini 12". No staleness marker in the DOM.
Why: primary user's #1 task, answered wrongly with full confidence, on a day the court is closed. Violates PRODUCT.md Principle 3 verbatim.
Fix: render the parsed `updated`; compare `date_label` to Asia/Jakarta today; relabel tab to the literal date on mismatch; suppress the hero count rather than printing a stale one.
Command: /impeccable harden

### [P0] "Detil" covers the case number on all 12 mobile cards
Verified: overlaps 41,44,53,57,71,71,53,51,52,69,19,72 px horizontally x 34px vertically. 12 links, 1 unique accessible name.
Fix: at <=760px move `.sipp-card-link` to its own full-width row; `aria-label="Detil perkara {nomor} - buka SIPP di tab baru"`. Cap `.sipp-chip` radius at 8px above one line (one chip is 6 lines / 121px tall with a 60px radius curving through its own copy).
Command: /impeccable adapt

### [P1] Nothing actionable above the fold at 1366x768
295px chrome = 38.4% of viewport. Hero h2 spans 198px / 3 lines. Primary CTA at y=830, 62px below the fold. 17 interactive elements above the fold, 0 are tasks.
Fix: collapse `.header-brand` 193px to ~104px at >=1024px; drop "Selamat Datang di" from the hero h2.
Command: /impeccable layout

### [P1] Token file exists; the page is not built from it
`:root` defines 12 colours, 2 shadows, 2 radii, 1 spacing var. Rendered: 24 text colours, 33 backgrounds, 17 shadows, 10 radii, 36 spacing values, 20 gaps, 37 font-sizes, 7 weights. No `button,input,select{font:inherit}` in 13,952 lines, producing 32 Arial strings including all four DIPA tabs and the search button.
Command: /impeccable typeset

### [P2] Every section is spatially equal, so the page makes no argument
All 5 inter-section gaps measure exactly 40px. Variance zero. 9 identical eyebrows. `Kinerja & Akuntabilitas` (a sidebar widget) is an h2, same level as `Jadwal Sidang`.
Fix: three tiers — feature 96px, standard 56px, ancillary 32px.
Command: /impeccable layout

### [P2] Open/closed status fails contrast in both themes
Light 2.35:1; dark 1.86:1. `Besok` tab in dark mode 1.48:1. Highest-anxiety string on the page.
Command: /impeccable audit

## Persona Red Flags

Jordan (Natuna resident, needs tomorrow's hearing): tab says "Hari Ini / Kamis, 23 Jul. 2026" on a Sunday. Board starts at 2.26 screens desktop / 1.88 mobile and runs 3,169px on mobile. `Pihak: Tidak dipublikasikan` unexplained. Empty state offers no phone number though 0773-3211203 is on the page. Hero chip says "Ranai"; brand address says "Sungai Ulu, Bunguran Timur".

Sam (keyboard + screen reader, 200% zoom): `<main id="content">` has no `tabindex="-1"`; skip-link lands at y=1,363, past the hero CTAs and all 8 quick-link tiles. No `scroll-padding-top` against a 62px fixed nav. 83 menu links open on `:focus-within`. Sidebar reading order inverted — `Jam Layanan` announced after the map. 12 links named "Detil". `.dark-toggle` 38x38.

Alex (advocate, weekly): 15 SIPP links, highest at y=1,146. Mobile bottom-nav "Perkara" is zero-scroll — the desktop layout is measurably slower for the repeat user. Hero rotates every 7,000ms and `Telusuri Perkara` exists only on slide 1.

## Minor Observations

- `hero-slider.php:137-141` hardcodes excerpt copy for article IDs 208 and 209 — violates the DB-not-template constraint.
- Sidebar ends at y=3,739; main runs to y=6,000 — 2,261px of empty right rail, 36% of page height.
- `maklumat-pelayanan-2026.webp` 324 KB, 1414x2000 natural, rendered 106x150 = 13.34x overscale.
- 2.92 MB / 50 requests for an audience defined by unstable connectivity.
- Hero h2 (71.7px) is larger than the page h1 (48px); both say the institution's name.
- YouTube rail lists "izin besuk tahanan" twice.
- `.quick-links` is `display:none` on mobile; six destinations exist only on desktop.
- DIPA period tabs measure 62.61x30px — below the owner's 44px contract, and not covered by `test_accessibility_performance_hardening.php`.

## Questions to Consider

1. `Sidang Keliling` is why this court's website matters more than a mainland court's. Why is it a 12px green pill in third position?
2. The Badilum mandate is a floor for availability. What is your floor for emphasis, and who wrote it down?
3. `sipp-schedule.php:144` parses SIPP's `updated` and discards it; the module 200px below prints its own timestamp unasked. Which module was Principle 3 written about?
4. Today is Sunday and the page says "Agenda hari ini: 12". If someone books a boat on that number, which of your three success measures records the failure?
5. 46 photographs, all shrunk. If you had to spend one full screen on one photograph, which one, and what would it say?
