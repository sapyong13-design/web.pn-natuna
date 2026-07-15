# News Deduplication and Excerpt Cleanup Design

## Goal
Remove duplicate news articles from public local listings and restore useful excerpts for imported articles without headlines/excerpts. Keep all work local until explicit push instruction.

## Duplicate identity
- Scope: active Joomla articles in category 12 (Berita).
- Read non-empty `image_intro`, falling back to `image_fulltext`.
- Resolve only local files below repository `images/`.
- Compute SHA-256 from actual image bytes; paths and filenames do not establish identity.
- Articles sharing one image hash form a duplicate candidate group.

## Winner rule
1. Prefer an article whose alias does not start with `legacy-`.
2. If all candidates are legacy imports, prefer latest valid `publish_up`, then longest meaningful body, then lowest article ID for deterministic behavior.
3. Move every losing article to Joomla trash with `state = -2`; do not hard-delete rows.
4. Public category queries therefore expose exactly one winner per duplicate image.

## Excerpt repair
- Scope: surviving active imported articles with empty `introtext` and meaningful `fulltext`.
- Parse and sanitize body text, discard image-only/empty paragraphs, collapse whitespace, and use first meaningful paragraph.
- Truncate through existing Joomla card behavior; stored excerpt remains a complete clean paragraph.
- Do not fabricate titles. Existing article `title` remains card headline.

## Artifacts
- Focused Python contract test for hash grouping, deterministic winner selection, and excerpt extraction.
- Local cleanup tool producing an idempotent SQL migration and audit report.
- Apply migration only to local `pn_natuna_rebuild`.
- Do not commit or push until user explicitly requests it.

## Verification
- Run cleanup twice; duplicate/trash counts remain stable.
- Query active category 12 rows and prove no repeated primary-image SHA-256.
- Query surviving imported rows and report any empty excerpt with meaningful body.
- Browser QA `/berita` and representative detail pages at desktop and mobile widths.
- Confirm trashed duplicate URLs no longer appear in public listing.
