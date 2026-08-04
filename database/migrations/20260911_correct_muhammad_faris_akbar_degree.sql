-- Correct Muhammad Faris Akbar's degree everywhere without changing other personnel.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(introtext, 'Muhammad Faris Akbar, A.Md., A.B.', 'Muhammad Faris Akbar, A.Md.'),
        'Muhammad Faris Akbar, A.Md.A.B.', 'Muhammad Faris Akbar, A.Md.'
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE introtext LIKE '%Muhammad Faris Akbar, A.Md%A.B.%';

UPDATE #__content
SET `fulltext` = REPLACE(
        REPLACE(`fulltext`, 'Muhammad Faris Akbar, A.Md., A.B.', 'Muhammad Faris Akbar, A.Md.'),
        'Muhammad Faris Akbar, A.Md.A.B.', 'Muhammad Faris Akbar, A.Md.'
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE `fulltext` LIKE '%Muhammad Faris Akbar, A.Md%A.B.%';

UPDATE #__modules
SET content = REPLACE(
        REPLACE(content, 'Muhammad Faris Akbar, A.Md., A.B.', 'Muhammad Faris Akbar, A.Md.'),
        'Muhammad Faris Akbar, A.Md.A.B.', 'Muhammad Faris Akbar, A.Md.'
    )
WHERE content LIKE '%Muhammad Faris Akbar, A.Md%A.B.%';
