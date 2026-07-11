# Court Profile Family Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development.

**Goal:** Build a coherent Court Profile gateway, cinematic history hero, accessible unit navigation, and truthful jurisdiction/status refinements without rewriting already-polished content.

**Architecture:** Idempotent SQL updates article HTML; existing archetype classes are preserved and extended; one scoped CSS system handles landing/history/unit/roster refinements; no menu routes change.

## Constraints

- Preserve all 14 routes and factual roster/narrative content.
- Never invent jurisdiction boundaries or silently correct NIP.
- History hero uses `images/hero/gedung-pn-natuna-2026.webp` and reduced-motion-safe cinema treatment.
- No commit/push.

### Task 1: Rebuild Profile Landing
- Capture canonical menu routes.
- Replace article 25 flat links with grouped institutional gateway and canonical nested URLs.
- Remove unrelated links.

### Task 2: Upgrade History Hero
- Replace primary history media with latest WebP cinematic hero.
- Preserve timeline/legal sources and verified copy.
- Add dimensions/caption and reduced-motion-safe styling.

### Task 3: Refine Organization, Jurisdiction, and Narrative Pages
- Remove jurisdiction internal placeholder; publish truthful validation status/contact CTA.
- Add accessible organization chart summary/status.
- Add source/effective-date notes only where verified.

### Task 4: Refine Roster and Clerkship Unit Pages
- Add update/status notes without changing personnel facts.
- Add clerkship unit navigator and unit sibling/current navigation.
- Add diagram purpose/provenance text and mobile wrapping.

### Task 5: Shared CSS, QA, Persistence
- Add scoped gateway/cinematic/profile-nav/status styles, dark/mobile/focus/reduced-motion.
- Verify all routes and content preservation at 320/390/760/761/1280/1440.
- Save SQL delta, apply idempotently, create fresh dump, update HANDOFF.
- Independent review and fixes.
- Leave uncommitted/unpushed.
