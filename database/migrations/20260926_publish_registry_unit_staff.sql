-- Publish each registry unit's own staff beside the responsible officer.
-- Cards are copied verbatim from the Kepaniteraan roster so NIP, pangkat/golongan, jabatan, and pendidikan stay identical to the source article.

SET @anchor := '<section class="roster-section"><div class="roster-section-head"><h2>Ruang Lingkup Layanan</h2>';
SET @staff := '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Staf Kepaniteraan Pidana</h2><span class="roster-count">3 orang</span></div><div class="roster-grid"><article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kepaniteraan/marihod.png" alt="Marihod Tua Lubis, S.H." loading="lazy"><span class="roster-degree">S1</span></div><div class="roster-body"><h3 class="roster-name">Marihod Tua Lubis, S.H.</h3><div class="roster-role-row"><span class="roster-role">Analis Perkara Peradilan</span><span class="roster-role">Kepaniteraan Pidana</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>200002282024051001</dd></div><div><dt>Pangkat/Gol.</dt><dd>Penata Muda / III.a</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/juprizal.png" alt="Juprizal, A.Md., A.B." loading="lazy"><span class="roster-degree">D3</span></div><div class="roster-body"><h3 class="roster-name">Juprizal, A.Md., A.B.</h3><div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Pidana</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199510102025061005</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pengatur / II.c</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/yuningsih.png" alt="Yuningsih" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Yuningsih</h3><div class="roster-role-row"><span class="roster-role">Kepaniteraan Pidana</span><span class="roster-role">PPPK</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>197906022025212014</dd></div></dl></div></article></div></section>';
UPDATE #__content
SET introtext = REPLACE(introtext, @anchor, CONCAT(@staff, @anchor)),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-pidana'
  AND introtext LIKE CONCAT('%', @anchor, '%')
  AND introtext NOT LIKE '%roster-section-staff%';

SET @anchor := '<section class="roster-section"><div class="roster-section-head"><h2>Ruang Lingkup Layanan</h2>';
SET @staff := '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Staf Kepaniteraan Perdata</h2><span class="roster-count">2 orang</span></div><div class="roster-grid"><article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/asturi.png" alt="Asturi Periyadi, A.Md. A.B." loading="lazy"><span class="roster-degree">D3</span></div><div class="roster-body"><h3 class="roster-name">Asturi Periyadi, A.Md. A.B.</h3><div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Perdata</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199705062025061012</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pengatur / II.c</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/kartina.png" alt="Kartina" loading="lazy"><span class="roster-degree">SMK</span></div><div class="roster-body"><h3 class="roster-name">Kartina</h3><div class="roster-role-row"><span class="roster-role">Kepaniteraan Perdata</span><span class="roster-role">PPPK</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>199210082025212043</dd></div></dl></div></article></div></section>';
UPDATE #__content
SET introtext = REPLACE(introtext, @anchor, CONCAT(@staff, @anchor)),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-perdata'
  AND introtext LIKE CONCAT('%', @anchor, '%')
  AND introtext NOT LIKE '%roster-section-staff%';

SET @anchor := '<section class="roster-section"><div class="roster-section-head"><h2>Layanan Permohonan</h2>';
SET @staff := '<section class="roster-section roster-section-staff"><div class="roster-section-head"><h2>Staf Kepaniteraan Hukum</h2><span class="roster-count">2 orang</span></div><div class="roster-grid"><article class="roster-card"><div class="roster-photo"><img src="images/profil/pegawai/kesekretariatan/dion.png" alt="Dion Boy Ardita, A.Md. A.B." loading="lazy"><span class="roster-degree">D3</span></div><div class="roster-body"><h3 class="roster-name">Dion Boy Ardita, A.Md. A.B.</h3><div class="roster-role-row"><span class="roster-role">Dokumentalis Hukum</span><span class="roster-role">Kepaniteraan Hukum</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>200104212025061010</dd></div><div><dt>Pangkat/Gol.</dt><dd>Pengatur / II.c</dd></div></dl></div></article><article class="roster-card"><div class="roster-photo"><img src="/images/profil/pegawai/pppk/ardiansyah.png" alt="Ardiansyah" loading="lazy"><span class="roster-degree">SMA</span></div><div class="roster-body"><h3 class="roster-name">Ardiansyah</h3><div class="roster-role-row"><span class="roster-role">Kepaniteraan Hukum</span><span class="roster-role">PPPK</span></div><dl class="roster-meta"><div><dt>NIP</dt><dd>1990010620252101027</dd></div></dl></div></article></div></section>';
UPDATE #__content
SET introtext = REPLACE(introtext, @anchor, CONCAT(@staff, @anchor)),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'kepaniteraan-hukum'
  AND introtext LIKE CONCAT('%', @anchor, '%')
  AND introtext NOT LIKE '%roster-section-staff%';
