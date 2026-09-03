-- Restore the canonical featured-card DOM for the active Chief Judge profile.
-- The prior production edit moved its eyebrow out of .roster-featured-body,
-- causing CSS Grid to place the identity data below the photograph.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '(?s)<article class="roster-featured">.*?</article>',
        '<article class="roster-featured"><div class="roster-featured-photo"><img src="images/profil/pegawai/hakim/joko-ciptanto.jpg" alt="Joko Ciptanto, S.H., M.H." loading="lazy"><span class="roster-degree">S2</span></div><div class="roster-featured-body"><span class="roster-eyebrow">Ketua Pengadilan</span><h3 class="roster-featured-name">Joko Ciptanto, S.H., M.H.</h3><dl class="roster-meta"><div><dt>NIP</dt><dd>198006162008051001</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pembina / IV.a</dd></div><div><dt>Pendidikan</dt><dd>S2</dd></div></dl></div></article>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'profil-hakim'
  AND introtext LIKE '%Joko Ciptanto, S.H., M.H.%'
  AND introtext LIKE '%<article class="roster-featured">%';
