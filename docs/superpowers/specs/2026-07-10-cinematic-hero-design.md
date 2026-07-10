# Cinematic Homepage Hero Design

## Goal

Replace the low-resolution building backdrop with `images/hero/gedung-pn-natuna-2026.png` and turn the homepage hero into a cinematic, informative civic opening scene without sacrificing performance or accessibility.

## Visual Direction

“Civic Cinema, Natuna di bawah langit Indonesia.” The Indonesian flag and courthouse are the visual anchors. A warm maroon grade, left-side editorial scrim, lower vignette, fine grain, and restrained gold light sweep create depth while preserving the photograph’s natural blue sky and architectural detail.

## Components

1. Full-bleed high-resolution courthouse photograph with responsive focal positioning.
2. Layered color grade, vignette, and atmospheric texture without backdrop blur.
3. Slow 2–3% push-in, disabled for reduced motion and paused offscreen.
4. Asymmetric lower-left copy with stronger Fraunces title and a “Kelas II” institutional label.
5. Live service ribbon containing dynamic service status, hours, current date, and Ranai location.
6. Two primary actions: “Layanan Pengadilan” and “Telusuri Perkara”. Supporting links remain available but visually subordinate.
7. Editorial photo caption: “Gedung Pengadilan Negeri Natuna · Ranai, Kepulauan Riau”.
8. Gold light sweep during active slide entrance; crossfade remains compatible with existing carousel controls.
9. News slide retains existing dynamic data and preview behavior.
10. Responsive composition for 1920, 1440, 768, and 390 px, with no horizontal overflow.

## Accessibility and Performance

The backdrop remains decorative with empty alt text. Text contrast must remain readable across the bright clouds. Focus indicators stay visible. Motion is removed under `prefers-reduced-motion`. No new library, blur filter, or continuous JavaScript animation is introduced. Image dimensions are declared to reduce layout shift.

## Verification

Confirm new image URL, hero content, two primary actions, service ribbon fields, caption, active slide transition, reduced-motion rules, 1920/1440/768/390 layouts, no overflow, ordered headings, and readable light/dark rendering.
