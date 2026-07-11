# Transparency Family Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement tasks with focused review.

**Goal:** Redesign Transparency landing and 13 child services into one informative, accessible document portal while preserving every existing document URL.

**Architecture:** Normalize Joomla article HTML into shared landing/archive/resource/status classes, add two canonical child menu/article records, style one reusable responsive system, and persist SQL delta plus full DB snapshot.

**Tech Stack:** Joomla articles/menu DB, SQL delta, HTML/CSS, Chromium QA.

## Constraints

- Landing remains article id 45.
- Preserve all existing external document URLs exactly.
- Add canonical `/transparansi/lelang-barang-jasa` and `/transparansi/laporan-pelayanan-informasi-publik`.
- Never invent unavailable documents or metadata.
- Missing periods use explicit citizen-facing status.
- No plugin/new library.
- Commit and push only after full verification.

### Task 1: Normalize Landing and Routes

- Capture all current external URLs and landing card destinations.
- Create SQL delta updating article 45 to four groups/13 canonical cards and `/kontak` CTA.
- Add two Joomla articles and nested menu children with valid assets/nested-set ordering.
- Apply SQL and verify 14 routes.

### Task 2: Redesign Annual Archives

- Update Ringkasan LKjIP, Laporan Tahunan, SAKIP, Laporan Keuangan, LHKPN/LHKASN.
- Preserve every Drive URL.
- Newest-first structured document rows, naming explanations, and explicit missing/current status.

### Task 3: Redesign Periodic Archives

- Update Realisasi Anggaran, SKM/IKM, SPAK, Survei Harian.
- Structure by year and month/quarter, preserve URLs, expose missing-period statuses and terminology explanations.

### Task 4: Redesign Resource/Policy/New Services

- Update E-Brosur with text-first resource action and dimensioned QR.
- Replace Peraturan placeholder with citizen-facing curation status.
- Populate Lelang and Laporan Pelayanan Informasi Publik with honest scope/status and links only where verified.

### Task 5: Shared Visual and Navigation System

- Add transparency-local breadcrumb/grouped sibling navigation/current state in every child.
- Add `.transparency-*` CSS for landing, archive rows, chips, empty states, mobile/dark/focus/reduced motion.
- Fix Indonesian document/schema/speech language where controlled by template.

### Task 6: Verification, Persistence, Handoff

- Verify all routes, menu/card parity, external URL preservation, missing-period labels, touch targets, light/dark, 320/390/760/761/1280/1440, no overflow.
- Review independently and fix findings.
- Save SQL delta and full DB dump.
- Update HANDOFF/knowledge base.
- Commit and push branch.
