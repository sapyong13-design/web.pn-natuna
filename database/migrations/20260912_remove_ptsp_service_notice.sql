-- Remove the informational notice below the PTSP service list.
-- Preserve service details, documentary photos, and electronic-service CTA.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?is)<div[[:space:]]+class="svc-note">[[:space:]]*<svg.*?</svg>[[:space:]]*<p>[[:space:]]*Rincian jenis layanan akan terus disesuaikan.*?</p>[[:space:]]*</div>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE alias='jenis-layanan-pada-ptsp-pengadilan-negeri-natuna'
  AND introtext LIKE '%Rincian jenis layanan akan terus disesuaikan%';

UPDATE #__content
SET `fulltext` = REGEXP_REPLACE(
        `fulltext`,
        '(?is)<div[[:space:]]+class="svc-note">[[:space:]]*<svg.*?</svg>[[:space:]]*<p>[[:space:]]*Rincian jenis layanan akan terus disesuaikan.*?</p>[[:space:]]*</div>[[:space:]]*',
        ''
    ),
    modified=UTC_TIMESTAMP(),
    modified_by=0
WHERE alias='jenis-layanan-pada-ptsp-pengadilan-negeri-natuna'
  AND `fulltext` LIKE '%Rincian jenis layanan akan terus disesuaikan%';
