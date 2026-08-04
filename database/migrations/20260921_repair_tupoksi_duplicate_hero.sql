-- Repair the malformed fragment and duplicate hero left by the prior Tupoksi distillation.
SET @hero_marker := '<section class="tupoksi-hero-card">';
SET @grid_marker := '<section class="tupoksi-grid">';
SET @first_hero_end := LOCATE('</section>', LOCATE(@hero_marker, (SELECT introtext FROM #__content WHERE alias='tugas-pokok-fungsi' LIMIT 1)));

UPDATE #__content
SET introtext = CONCAT(
        LEFT(introtext, @first_hero_end + CHAR_LENGTH('</section>') - 1),
        '\n\n  ',
        @grid_marker,
        SUBSTRING_INDEX(introtext, @grid_marker, -1)
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'tugas-pokok-fungsi'
  AND introtext LIKE '%s="tupoksi-page">%'
  AND (CHAR_LENGTH(introtext) - CHAR_LENGTH(REPLACE(introtext, @hero_marker, ''))) / CHAR_LENGTH(@hero_marker) > 1
  AND introtext LIKE CONCAT('%', @grid_marker, '%');
