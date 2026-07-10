# Mobile Homepage Redesign

## Goal

Transform the PN Natuna homepage at widths up to 760 px into a clear, one-handed civic service experience that is visually refined, lightweight, and organized around user intent rather than desktop module structure.

## Mobile Shell

Use a compact 72–84 px brand header with shortened identity, a clear menu control, and a 56 px sticky state. Replace the current quick bar with five actions: Beranda, Layanan, Perkara, Pengaduan, Kontak. Active state uses the gold accent. Respect safe-area insets and move WhatsApp, accessibility, and back-to-top controls above the bar without collisions.

## Hero and Start Here

Keep the cinematic photograph but reduce mobile density: two-line introduction, status and hours only, one primary and one secondary CTA. Keep desktop information unchanged. Add a “Mulai dari sini” 2×2 action grid after the hero for Jadwal Sidang, Telusuri Perkara, Ajukan Informasi, and Buat Pengaduan.

## User-Intent Services

Add a mobile-only intent section with four accessible disclosure groups: Saya punya perkara, Saya membutuhkan layanan, Saya mencari informasi, and Saya ingin menyampaikan keluhan. Each exposes relevant existing routes. Use native `details`/`summary` semantics and 44 px minimum targets.

## Content and Sidebar

At mobile widths, turn supporting sidebar modules into horizontal snap rails or compact blocks. Keep Role Model, Kinerja/DIPA, and Instagram understandable without stacking oversized cards. Limit initial news and feed density through CSS progressive disclosure; retain “view all” routes. Instagram remains lazy and only loads the active embed near the viewport.

## Visual System and Motion

Use warm tinted surfaces, maroon for primary actions, gold sparingly, 14–18 px radii, restrained shadows, stronger type hierarchy, and 48–64 px section rhythm. Touch interactions use 160–280 ms ease-out and reduced-motion overrides. Use `content-visibility` for below-fold sections.

## Verification

Verify 390, 430, 760, 768, 1440, and 1920 px. Mobile must have no horizontal overflow, no floating-control collisions, 44 px touch targets, logical headings, usable keyboard focus, correct bottom navigation, compact hero, visible start-here and intent sections, compact sidebar rails, preserved desktop layout, and deferred Instagram embeds.
