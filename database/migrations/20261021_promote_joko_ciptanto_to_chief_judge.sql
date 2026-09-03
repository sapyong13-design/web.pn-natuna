-- Update only active institutional surfaces after Joko Ciptanto, S.H., M.H. became Ketua Pengadilan Negeri Natuna.
-- Historical news, assessment evidence, and archival documents remain unchanged.

UPDATE #__content
SET
    title = 'Sambutan Ketua Pengadilan',
    introtext = REPLACE(
        REPLACE(
            REPLACE(introtext, 'Sambutan Wakil Ketua', 'Sambutan Ketua Pengadilan'),
            'Wakil Ketua Pengadilan Negeri Natuna', 'Ketua Pengadilan Negeri Natuna'
        ),
        'Wakil Ketua</p>', 'Ketua Pengadilan</p>'
    ),
    metadesc = 'Sambutan Ketua Pengadilan Negeri Natuna mengenai keterbukaan informasi dan layanan peradilan.',
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'kata-sambutan';

UPDATE #__content
SET
    introtext = REPLACE(introtext, 'Wakil Ketua Pengadilan', 'Ketua Pengadilan'),
    `fulltext` = REPLACE(`fulltext`, 'Wakil Ketua Pengadilan', 'Ketua Pengadilan'),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'profil-hakim';

UPDATE #__modules
SET
    content = REPLACE(content, 'Joko Ciptanto, S.H., M.H - Wakil Ketua Pengadilan Negeri Natuna', 'Joko Ciptanto, S.H., M.H. - Ketua Pengadilan Negeri Natuna')
WHERE id = 482
  AND module = 'mod_custom';

UPDATE #__menu
SET title = 'Sambutan Ketua Pengadilan'
WHERE menutype = 'mainmenu'
  AND alias = 'kata-sambutan';
