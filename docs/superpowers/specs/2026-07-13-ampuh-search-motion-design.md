# AMPUH Search and Motion Refinement Design

## Goal
Improve AMPUH document discovery and hierarchy without making institutional UI noisy.

## Approved scope
1. Empty search resets document visibility, result copy, empty state, and disclosures while preserving active GOBI filter.
2. Matching terms receive accessible, non-destructive visual highlighting in document names.
3. Result status reports document and GOBI counts; active search exposes `Bersihkan pencarian`.
4. Search tools become contextually sticky with solid token-based surface.
5. GOBI remains dominant, checklist uses a clear index badge, sub-checklist remains quieter.
6. Document rows strengthen filename/type hierarchy and keyboard focus.
7. State motion uses opacity and transform at 180–220 ms with exponential ease-out; search updates do not stagger.
8. Existing global back-to-top control remains canonical and gains AMPUH-aware visibility/placement rather than adding a duplicate.

## Interaction contracts
- Query normalization uses Indonesian locale and remains case/diacritic tolerant as currently implemented.
- Search counts only document result nodes, not matching branch titles.
- Clearing search restores all nodes permitted by active GOBI filter and clears status text.
- Search opens ancestors only for matching files.
- Highlighting uses `<mark class="ampuh-directory__match">` and restores original filename content before each query.
- `prefers-reduced-motion: reduce` disables reveal, smooth scrolling, and decorative transitions.
- Empty or invalid sub-checklist Drive URLs render no action; valid public Google Drive URLs render their action.

## Responsive and accessibility
- Sticky toolbar must not overlap site header or focused controls.
- Result message remains an `aria-live="polite"` status.
- Clear control and all toggles retain at least 44 px targets where primary.
- Highlight contrast and focus indicators work in light and dark themes.
- Mobile back-to-top control must not cover quick actions or document content.

## Final command-header refinement

- Hero, indeks koleksi, dan search menjadi satu institutional command header.
- Desktop memakai rail GOBI dengan fade mask, panah 44×44, smooth scroll, dan fill state aktif; mobile memakai select GOBI.
- Kicker, heading, deskripsi, dan CTA memakai entrance choreography pendek; disclosure memakai ikon dua garis CSS dan motion 180–220 ms.
- Watermark `2026` memakai `clamp(4.8rem, 8.8vw, 7.5rem)` dengan clearance separator yang terverifikasi.
- Deployment target pertama adalah staging `new.pn-natuna.go.id`; manual operasional ada di `CPANEL-STAGING-CUTOVER-RUNBOOK.md`.

## Verification
Focused renderer and interaction contracts, dataset E2E, then browser QA at desktop/mobile, light/dark, reduced motion, matching query, no-match query, and clear/reset.