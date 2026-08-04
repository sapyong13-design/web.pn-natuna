-- Remove every personnel "Status profil" callout from profile rosters.
-- Cover all roster pages by component class, not by individual article alias.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?is)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE introtext REGEXP '(?i)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"';

UPDATE #__content
SET `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?is)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE `fulltext` REGEXP '(?i)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"';

UPDATE #__modules
SET content = REGEXP_REPLACE(
        content,
        '(?is)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"[[:space:]]+role="note">.*?</aside>[[:space:]]*',
        ''
    )
WHERE content REGEXP '(?i)<aside[[:space:]]+class="profile-note[[:space:]]+roster-status"';
