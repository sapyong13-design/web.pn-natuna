# Transparency Family Redesign

Date: 2026-07-11
Status: Approved direction
Scope: `/transparansi` and 13 canonical child services

## Goal

Turn Transparency into a coherent public-accountability portal where citizens can understand report scope, reporting period, document type, publication status, and destination without scanning bare Google Drive links.

## Information Architecture

Four groups:

1. Performance: Ringkasan LKjIP, Laporan Tahunan, SAKIP.
2. Finance and assets: Realisasi Anggaran, Laporan Keuangan, LHKPN/LHKASN, Lelang Barang dan Jasa.
3. Public service quality: SKM/IKM, SPAK, Survei Harian, Laporan Pelayanan Informasi Publik.
4. Public information: E-Brosur, Peraturan dan Kebijakan.

Joomla menu becomes canonical route inventory. Add two official child pages beneath menu 108:

- `/transparansi/lelang-barang-jasa`
- `/transparansi/laporan-pelayanan-informasi-publik`

Preserve all existing child URLs. Landing cards use exact canonical URLs.

## Shared Page Shell

Every child receives:

- Breadcrumb/back link to Transparency.
- Group label, title, concise scope explanation.
- Current reporting status and last-updated context when known.
- Grouped sibling navigation with current marker.
- Structured archive rows rather than browser-default lists.
- Clear file/folder/Google Drive/new-tab labels.
- Citizen-facing empty/status state when documents are unavailable.

## Archive Archetypes

- Annual: newest year first.
- Periodic: year sections with quarter/month rows.
- Resource collection: QR/image secondary, text link primary.
- Policy library: curated categories; until populated, honest service-status state without internal editorial language.

Each document row supports: period, title, destination type (PDF/file/folder), source (Google Drive), and open-new-tab cue. File size/format only shown when verified; never invent metadata.

## Content Integrity

- Laporan Tahunan 2023: show `Belum tersedia pada portal` status, not fake link.
- Realisasi Anggaran April 2026: show missing-period status between March and May.
- Survei Harian Nov–Dec 2025 and current 2026: state publication coverage factually.
- Explain PN Ranai→PN Natuna naming transition in LKjIP archive.
- Explain SKM/IKM relationship.
- Explain LHKPN/LHKASN scope.
- Explain “survei harian” files are monthly recaps.
- Replace Peraturan placeholder with public-facing curation status.

## Landing

- Hero with portal purpose, latest coverage summary, and PPID/contact actions.
- Four grouped sections and 13 canonical cards.
- Compact status chips: archive cadence, latest period, file/folder type where established.
- No duplicate route aliases.
- CTA contact uses `/kontak`.

## Accessibility and Responsive

- One content h1; court brand heading semantics must not create competing page h1.
- Root language and schema language Indonesian.
- External links announce Google Drive/new tab in visible and accessible text.
- Document rows minimum 44px, strong focus-visible, long labels wrap.
- Mobile uses one-column archive rows and compact sibling disclosure/navigation.
- No horizontal overflow at 320–1440px.
- Dark mode and reduced motion supported.

## Data and Delivery

Content remains Joomla articles but is normalized into reusable HTML classes. Deliver SQL delta for article/menu updates and a fresh database dump. No plugin or page builder. CSS namespace `.transparency-*`/`.transparansi-*` and reuse existing tokens.

## Verification

- All 14 routes (landing + 13 children) resolve.
- Landing cards and Joomla child menu agree exactly.
- Every existing document URL preserved.
- Missing periods display status rather than disappearing.
- External link semantics, current sibling state, mobile touch targets, light/dark, and no overflow verified.
- `/transparansi` remains article id 45.
