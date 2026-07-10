# Mobile Navigation Repair Design

Date: 2026-07-11
Status: Approved design, pending specification review
Route scope: All public routes at viewport widths up to 760px
Desktop preservation: Existing layout at 761px and above must remain unchanged

## Problem

Current mobile navigation is unusable and visually fragmented. Drawer items stretch across the viewport height, nested menu state lacks clear controls, fixed bottom navigation and floating controls remain visible behind the open drawer, and backdrop coverage is incomplete. Header, drawer, and bottom navigation appear as unrelated systems.

## Design Direction

Use a structured right-side navigation drawer. It serves the full Joomla menu hierarchy while homepage bottom navigation remains a five-item shortcut bar. Visual language stays consistent with PN Natuna maroon, warm cream, muted gold, Fraunces headings, and Plus Jakarta Sans controls.

## Drawer Shell

- Open from the right at widths up to 760px.
- Width: `min(90vw, 360px)`; full viewport height using `100dvh` with a `100vh` fallback.
- Header remains fixed inside the drawer and contains compact court identity plus a 44px minimum close control.
- Navigation content scrolls independently below the fixed drawer header.
- Footer contains dark-mode control and optional concise contact action; it stays after navigation content rather than floating over it.
- Background uses warm cream surfaces with maroon text and restrained gold accents. Avoid a full saturated-red drawer surface.
- Backdrop covers the entire viewport below all drawer content and above the page shell.

## Menu Hierarchy

- Top-level items render as a vertical list with 48px minimum row height.
- Direct links navigate normally.
- Items with children expose a dedicated expansion button with chevron and accessible label. Link and expansion control remain separate targets.
- Expanded children appear immediately below their parent in document flow.
- Second-level children use a subtle tinted group surface and indentation.
- Third-level children remain supported with one additional indentation step, not a detached flyout.
- Only one top-level branch should be expanded at a time. A nested branch inside it may remain expanded.
- Current route uses `aria-current="page"`, stronger weight, and a gold indicator plus text treatment so state does not rely on color alone.
- Menu labels use natural title case when possible; existing Joomla uppercase source labels may be visually transformed without changing accessible text.

## Open and Close Behavior

Opening the drawer must:

1. Set menu toggle `aria-expanded="true"`.
2. Reveal drawer and backdrop.
3. Add a body state class and prevent background scrolling without losing the original scroll position.
4. Hide homepage bottom navigation, accessibility launcher, WhatsApp, and back-to-top controls from visual and pointer interaction.
5. Move focus to the drawer close button.
6. Preserve current-route expansion when applicable.

Closing the drawer must work through:

- Close button.
- Backdrop click.
- `Escape` key.
- Successful internal navigation selection when no browser navigation occurs immediately.

Closing restores controls, body scroll position, and focus to the original menu toggle.

## Accessibility

- Focus remains inside the open drawer using a lightweight focus trap; no new library.
- Drawer uses an appropriate navigation/dialog relationship and accessible label.
- Expansion buttons expose `aria-expanded` and `aria-controls`.
- Hidden menu content is not keyboard-focusable or exposed as active UI.
- All controls and links have at least 44×44px touch targets with at least 8px separation where targets are adjacent.
- `:focus-visible` treatment must reach 3:1 contrast against adjacent colors.
- Text remains readable at browser text zoom 200%; labels wrap instead of truncating.
- Motion respects `prefers-reduced-motion`.

## Responsive Compatibility

1. **Safe areas:** drawer header, footer, and homepage bottom bar use `env(safe-area-inset-*)`.
2. **Narrow screens:** layout remains usable at 320px, 360px, 390px, and 430px.
3. **Breakpoint safety:** drawer styles apply through 760px; desktop menu is unchanged from 761px.
4. **Landscape:** drawer content scrolls within available height and no control is unreachable.
5. **Text scaling:** 200% text zoom does not overlap, clip, or create horizontal scrolling.
6. **Touch input:** controls use `touch-action: manipulation`; no hover-only behavior.
7. **Keyboard and screen reader:** logical focus order, Escape close, correct expanded/current states.
8. **Reduced motion:** remove panel transition and decorative motion when requested.
9. **Fixed UI offsets:** closed bottom bar reserves page space only on homepage; open drawer suppresses competing fixed controls.
10. **Viewport stability:** no horizontal overflow, no `100vh` browser-chrome clipping, and no layout shift when sticky header changes state.

## Homepage Bottom Navigation

- Keep five items: Beranda, Layanan, Perkara, Pengaduan, Kontak.
- Keep it homepage-only.
- Hide it completely while drawer is open.
- Maintain safe-area bottom padding and minimum 56px item height.
- Ensure floating controls sit above it only on homepage and reset on internal pages.

## Header Integration

- Initial header remains compact and branded.
- Sticky state remains 56px, including logo, identity, and menu button.
- Menu button receives a clearer icon-plus-label treatment and consistent pressed/focus state.
- Drawer may open from either initial or sticky header without geometry changes.

## Implementation Boundaries

Expected files:

- `templates/pn_natuna_2026/index.php`: drawer semantics and identity shell only.
- Joomla menu output override if required to separate parent link and expansion button cleanly; reuse an existing override if present.
- `templates/pn_natuna_2026/css/template.css`: replace conflicting mobile drawer rules rather than appending another override layer.
- `templates/pn_natuna_2026/js/template.js`: accordion, focus management, scroll lock, and control suppression.

No new dependency. No database menu restructuring unless source markup proves impossible to make accessible. No desktop visual redesign. No change to the five homepage bottom destinations.

## Verification

Behavioral checks:

- Open/close via toggle, close button, backdrop, and Escape.
- Focus enters drawer, cycles inside, and returns to toggle.
- Parent branches expand inline; no item is distributed through unused vertical space.
- Nested third-level menu remains reachable.
- Bottom bar and floating controls disappear while drawer is open and return afterward.
- Background scroll position is preserved.
- Active/current state is accurate on homepage and at least two internal routes.

Viewport checks:

- 320×568, 360×800, 390×844, 430×932.
- 667×375 and 844×390 landscape.
- 760px and 761px boundary.
- 200% browser text zoom or equivalent emulated scaling.
- Light and dark themes.
- Reduced-motion mode.

Acceptance criteria:

- No horizontal overflow at tested mobile widths.
- No fixed control overlaps the open drawer.
- Every visible interactive target is at least 44px in both dimensions.
- Drawer header and close control remain reachable in landscape.
- Desktop menu and homepage layout at 761px and above remain visually and behaviorally unchanged.
