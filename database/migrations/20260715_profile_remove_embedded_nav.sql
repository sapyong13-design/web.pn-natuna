-- Detail profile routes now receive one shared svc-subnav from the article override.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
  introtext,
  '(?s)<nav[[:space:]]+class="profile-unit-nav"[^>]*>.*?</nav>[[:space:]]*',
  ''
),
modified = NOW()
WHERE id IN (59, 107, 108, 109, 110)
  AND catid = 8
  AND introtext LIKE '%profile-unit-nav%';
