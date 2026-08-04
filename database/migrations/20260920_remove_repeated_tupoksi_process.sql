-- Remove the repeated four-step process block from the current escaped article payload.
SET @process_marker := '<section class="tupoksi-process"';
SET @grid_marker := '<section class="tupoksi-grid"';

UPDATE #__content
SET introtext = CONCAT(
        SUBSTRING_INDEX(introtext, @process_marker, 1),
        @grid_marker,
        SUBSTRING_INDEX(introtext, @grid_marker, -1)
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'tugas-pokok-fungsi'
  AND introtext LIKE CONCAT('%', @process_marker, '%')
  AND introtext LIKE CONCAT('%', @grid_marker, '%');
