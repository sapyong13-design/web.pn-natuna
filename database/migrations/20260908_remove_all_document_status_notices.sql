-- Remove every "Status dokumen" notice from published article content.
-- Match the complete neutral callout while preserving surrounding page content.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?is)<aside[[:space:]]+class="profile-note"[[:space:]]+role="note">[[:space:]]*<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE introtext REGEXP '(?i)<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>';

UPDATE #__content
SET `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?is)<aside[[:space:]]+class="profile-note"[[:space:]]+role="note">[[:space:]]*<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE `fulltext` REGEXP '(?i)<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>';

UPDATE #__modules
SET content = REGEXP_REPLACE(
        content,
        '(?is)<aside[[:space:]]+class="profile-note"[[:space:]]+role="note">[[:space:]]*<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>.*?</aside>[[:space:]]*',
        ''
    )
WHERE content REGEXP '(?i)<strong>[[:space:]]*Status dokumen[[:space:]]*</strong>';
