# Comprehensive Mobile Polish Design

## Scope

Final mobile pass for hero stability, integrity-poster readability, menu discovery, duplicated shortcuts, floating controls, video/announcement density, horizontal-rail affordance, typography, touch feedback, and adaptive prefetch.

## Decisions

- Mobile hero uses a stable minimum viewport per slide. Integrity artwork stays authentic; a focused card offers `Lihat poster penuh` to `/zona-integritas` rather than inventing a vertical image.
- Drawer gets an offline menu filter with clear button, result status, and empty state. Filtering opens matching ancestors without changing URLs.
- Mobile quick-links strip is hidden because `Mulai dari sini`, drawer, and bottom bar cover the same jobs.
- Homepage mobile WhatsApp floating control is hidden; WhatsApp remains in drawer and bottom navigation. Accessibility remains floating. Back-to-top threshold becomes 900px.
- YouTube rail shows three items on mobile; official-channel link handles the full collection. Scrollbar is hidden, active counter remains accessible.
- Sidebar and video rails expose a peek, `Geser untuk melihat lainnya`, and `x dari n` status.
- Tiny mobile copy increases to practical floors: navigation 0.78rem, metadata 0.72rem, bottom navigation 0.68rem.
- Touch controls receive restrained active feedback; reduced motion disables it.
- Integrity poster prefetch runs idle only when Save-Data is off and effective connection is not 2g/slow-2g.

## Verification

Source contracts plus browser QA at 320×568 and 390×844, light/dark, 200% text zoom, no horizontal overflow, stable drawer/footer, three visible mobile videos, no homepage WhatsApp float, and poster prefetch guards.