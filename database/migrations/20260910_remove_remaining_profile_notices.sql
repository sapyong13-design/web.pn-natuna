-- Remove all remaining profile-note callouts across profile and unit pages.
-- Preserve organization charts, service diagrams, rosters, and primary page content.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?is)<aside[[:space:]]+class="profile-note[^\"]*"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE introtext REGEXP '(?i)<aside[[:space:]]+class="profile-note';

UPDATE #__content
SET `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?is)<aside[[:space:]]+class="profile-note[^\"]*"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE `fulltext` REGEXP '(?i)<aside[[:space:]]+class="profile-note';

UPDATE #__modules
SET content = REGEXP_REPLACE(
        content,
        '(?is)<aside[[:space:]]+class="profile-note[^\"]*"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    )
WHERE content REGEXP '(?i)<aside[[:space:]]+class="profile-note';
