-- Remove Cania Kirana from the active Kepaniteraan roster after her transfer.
-- Unit tabs already derive their active rosters separately; Kepaniteraan Perdata contains no Cania card.
UPDATE #__content
SET introtext = REPLACE(
    REGEXP_REPLACE(
        introtext,
        '(?s)<article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/cania[.]jpg".*?</article>',
        ''
    ),
    '<h2>Staf Kepaniteraan</h2><span class="roster-count">8 orang</span>',
    '<h2>Staf Kepaniteraan</h2><span class="roster-count">7 orang</span>'
), modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='profil-kepaniteraan'
  AND introtext LIKE '%images/profil/pegawai/kepaniteraan/cania.jpg%';
