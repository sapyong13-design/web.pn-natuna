# Court Profile Family Redesign

Date: 2026-07-11
Status: Approved direction
Scope: `/profil-pengadilan` plus 13 descendants

## Goal

Create one coherent institutional-profile experience while preserving polished narrative, roster, and unit pages already built.

## Information Architecture

Landing article 25 becomes a grouped gateway:

1. Identity and mandate: History, Vision/Mission, Duties/Functions.
2. Organization and jurisdiction: Organization Structure, Jurisdiction.
3. People: Judges, Clerkship, Secretariat, PPPK.
4. Clerkship units nested under Clerkship: Criminal, Civil, Law, Fisheries.

All links use canonical nested routes. Remove unrelated social/contact/supervision links from the profile gateway.

## Landing

- Official institutional hero with court identity and concise purpose.
- Three/four editorial groups instead of a flat 16-link list.
- Context-rich cards using existing maroon/gold system.
- Clerkship card exposes four unit links.
- Mobile one-column and touch targets ≥44px.

## Cinematic History Hero

History article 54 keeps verified narrative/timeline/legal-source content but upgrades hero media to `images/hero/gedung-pn-natuna-2026.webp`.

- Wide cinematic courthouse hero.
- Dual-layer cover/contain treatment to preserve flag and building nameplate.
- Maroon color grade, vignette, edge feather, restrained Ken Burns/parallax only when motion is allowed.
- Strong caption/date/source affordance.
- `prefers-reduced-motion` disables movement.
- Existing old history image may remain in archive but no longer primary hero.

## Preserve and Lightly Refine

- Duties/Functions, Vision/Mission: retain factual copy; add source/effective-date area only where verified.
- Organization: retain chart and zoom; add version/status plus accessible hierarchy summary without inventing names.
- Judge/Clerkship/Secretariat/PPPK rosters: retain published data; add update/status note and mobile wrapping. Do not silently correct NIP.
- Clerkship landing: add unit navigator before roster.
- Unit pages: add parent/sibling navigator, diagram provenance/status, textual summary of what diagram represents; do not fabricate procedural steps.

## Jurisdiction

Remove internal placeholder language. Since verified boundary/village dataset is unavailable, publish a citizen-facing status page:

- Court jurisdiction overview limited to currently verified wording.
- Explain authoritative map/list is being validated.
- Contact action for service-jurisdiction questions.
- No inferred boundaries, villages, or islands.
- Architecture supports later map/table insertion.

## Shared Profile Navigation

Every scoped page receives compact breadcrumb/back link and grouped sibling navigation appropriate to its archetype. Unit pages show parent Clerkship and four unit siblings with current marker.

## Accessibility and Responsive

- One content h1; article-internal hero titles become h2 when Joomla page heading is h1.
- Diagram/chart has text equivalent describing purpose and link to full image; no false structural data.
- Images have dimensions, alt/caption.
- Long names/NIP/roles wrap.
- Light/dark, focus-visible, reduced-motion.
- No overflow at 320–1440px.
- Indonesian language/schema metadata retained.

## Data Delivery

Use one idempotent SQL delta updating article HTML. No menu URL changes or new routes. Add CSS within existing `sejarah-*`, `roster-*`, `unit-*`, and new scoped profile-gateway classes. Produce fresh DB dump and HANDOFF notes. Leave changes uncommitted/unpushed until explicit user instruction.

## Verification

- All 14 routes resolve.
- Landing links equal canonical menu descendants.
- Existing roster facts and external/legal links preserved.
- History hero uses newest WebP and reduced-motion fallback.
- Jurisdiction internal note removed without invented facts.
- Unit navigation/current state works.
- Heading, touch target, dark mode, mobile/desktop overflow matrix passes.
