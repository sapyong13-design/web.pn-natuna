-- Complete active leadership label cutover in both duplicate Profile Pengadilan landing articles.
UPDATE #__content
SET
    introtext = REPLACE(introtext, '<h3>Sambutan Wakil Ketua</h3>', '<h3>Sambutan Ketua Pengadilan</h3>'),
    `fulltext` = REPLACE(`fulltext`, '<h3>Sambutan Wakil Ketua</h3>', '<h3>Sambutan Ketua Pengadilan</h3>'),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE id IN (2, 25)
  AND (introtext LIKE '%<h3>Sambutan Wakil Ketua</h3>%' OR `fulltext` LIKE '%<h3>Sambutan Wakil Ketua</h3>%');
