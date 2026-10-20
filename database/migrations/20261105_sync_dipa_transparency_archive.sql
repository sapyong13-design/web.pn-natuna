-- Sync the transparency archive list with the DIPA widget.
-- tools/refresh-dipa.py updates module 816 (homepage "Kinerja & Akuntabilitas") from
-- the Drive folder, but it never touches article 40, so the transparency archive
-- stopped at Mei 2026 while the widget already carried Jun-Sep 2026.
-- Drive file IDs are the ones refresh-dipa.py resolved for those periods; the
-- Feb-Mei IDs already present in this article match module 816 exactly, which is
-- what makes the Jun-Sep mapping trustworthy.
--
-- Two phases, so the result does not depend on the starting state:
--   1. Strip all four cards wherever they sit. A partially applied list, or one
--      with cards in the wrong order, collapses back to the known baseline instead
--      of accumulating duplicates.
--   2. Re-insert top-down, each card anchored to the card directly below it, which
--      phase 1 has just guaranteed exists. Insert order Sep -> Agu -> Jul -> Jun,
--      so every anchor is present by the time it is used.
-- Anchoring all four to Mei (the obvious version) is wrong: from a state where Jun
-- is missing but Jul/Agu/Sep exist, it re-inserts Jun after Sep. Verified by
-- replaying against a deliberately damaged list.
-- The runner wraps this file in one transaction, so the phase-1 gap is never visible.

SET @card_jun := '<a class="transparency-document" href="https://drive.google.com/file/d/10qg6k5u9gosY2w4pw322LJOFQSgS5QYg/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Juni 2026</strong><small>Google Drive · buka di tab baru</small></span></a>';
SET @card_jul := '<a class="transparency-document" href="https://drive.google.com/file/d/18bRVbnv5c4lFmYgzpt0uRr_hwz-rzNsC/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Juli 2026</strong><small>Google Drive · buka di tab baru</small></span></a>';
SET @card_agu := '<a class="transparency-document" href="https://drive.google.com/file/d/1AsfU7MyA1hAygD0rXWjG94mzemFPQVwR/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Agustus 2026</strong><small>Google Drive · buka di tab baru</small></span></a>';
SET @card_sep := '<a class="transparency-document" href="https://drive.google.com/file/d/1azHxkOIF9DrDkbiT_gGpmXxQolEcjmH8/view?usp=sharing" target="_blank" rel="noopener"><span><strong>September 2026</strong><small>Google Drive · buka di tab baru</small></span></a>';
SET @card_mei := '<a class="transparency-document" href="https://drive.google.com/file/d/16iJHL_r9EudkOvmw-clD6t4GQwRUToJa/view?usp=sharing"';

-- Phase 1: strip the four cards so the list collapses to the known baseline.
UPDATE #__content
SET introtext = REPLACE(REPLACE(REPLACE(REPLACE(introtext,
        @card_jun, ''), @card_jul, ''), @card_agu, ''), @card_sep, '')
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND (LOCATE(@card_jun, introtext) > 0
    OR LOCATE(@card_jul, introtext) > 0
    OR LOCATE(@card_agu, introtext) > 0
    OR LOCATE(@card_sep, introtext) > 0);

-- Phase 2: re-insert top-down, each anchored to the card immediately below it.
UPDATE #__content
SET introtext = REPLACE(introtext, @card_mei, CONCAT(@card_sep, @card_mei)),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND LOCATE(@card_sep, introtext) = 0
  AND LOCATE(@card_mei, introtext) > 0;

UPDATE #__content
SET introtext = REPLACE(introtext, @card_sep, CONCAT(@card_agu, @card_sep)),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND LOCATE(@card_agu, introtext) = 0
  AND LOCATE(@card_sep, introtext) > 0;

UPDATE #__content
SET introtext = REPLACE(introtext, @card_agu, CONCAT(@card_jul, @card_agu)),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND LOCATE(@card_jul, introtext) = 0
  AND LOCATE(@card_agu, introtext) > 0;

UPDATE #__content
SET introtext = REPLACE(introtext, @card_jul, CONCAT(@card_jun, @card_jul)),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND LOCATE(@card_jun, introtext) = 0
  AND LOCATE(@card_jul, introtext) > 0;
