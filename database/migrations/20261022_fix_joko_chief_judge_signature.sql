-- Complete Joko Ciptanto's active title on the leadership welcome-page signature.
UPDATE #__content
SET
    introtext = REPLACE(introtext, '<small>Wakil Ketua</small>', '<small>Ketua Pengadilan</small>'),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'kata-sambutan'
  AND introtext LIKE '%<small>Wakil Ketua</small>%';
