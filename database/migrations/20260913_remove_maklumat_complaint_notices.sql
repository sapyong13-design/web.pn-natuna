-- Remove the informational callouts from Maklumat Pelayanan and Regulasi Pengaduan.
-- Preserve primary documents, regulations, procedures, and action links.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?is)<div[[:space:]]+class="svc-note">.*?</div>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE alias IN ('maklumat-pelayanan', 'regulasi-pengaduan')
  AND introtext REGEXP '(?i)<div[[:space:]]+class="svc-note">';

UPDATE #__content
SET `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?is)<div[[:space:]]+class="svc-note">.*?</div>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE alias IN ('maklumat-pelayanan', 'regulasi-pengaduan')
  AND `fulltext` REGEXP '(?i)<div[[:space:]]+class="svc-note">';
