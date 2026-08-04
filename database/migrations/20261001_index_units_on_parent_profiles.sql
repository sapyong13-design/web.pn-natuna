-- Parent profile pages index their own units as linked rows: unit name, responsible officer, and the unit's first published scope line.
-- Officer names and scope text are read from the unit articles, not authored here.
-- The index is appended inside the apparatus wrapper by trimming its final closing tag; REPLACE is avoided because it would hit every </div>.

-- profil-kepaniteraan
SET @index := '<section class="roster-section" aria-label="Unit Kepaniteraan"><div class="roster-section-head"><h2>Unit Kepaniteraan</h2><span class="roster-count">4 unit</span></div><ul class="unit-index"><li><a href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-pidana"><strong>Kepaniteraan Pidana</strong><span class="unit-index__officer">Ari Putra Utama, A.Md. A.B.</span><span class="unit-index__lead">Administrasi penerimaan, pencatatan, dan pengelolaan perkara pidana.</span></a></li><li><a href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-perdata"><strong>Kepaniteraan Perdata</strong><span class="unit-index__officer">Hadry B., S.H.</span><span class="unit-index__lead">Administrasi penerimaan dan pengelolaan perkara perdata.</span></a></li><li><a href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-hukum"><strong>Kepaniteraan Hukum</strong><span class="unit-index__officer">Jhivo Wilanda, S.H.</span><span class="unit-index__lead">Permohonan surat keterangan tidak pernah dipidana.</span></a></li><li><a href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-khusus-perikanan"><strong>Kepaniteraan Khusus Perikanan</strong><span class="unit-index__officer">Hadry B., S.H.</span><span class="unit-index__lead">Administrasi penerimaan dan pencatatan perkara perikanan.</span></a></li></ul></section>';
UPDATE #__content
SET introtext = CONCAT(LEFT(introtext, CHAR_LENGTH(introtext) - 6), @index, '</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kepaniteraan'
  AND introtext LIKE '%</div>'
  AND introtext NOT LIKE '%unit-index%';

-- profil-kesekretariatan
SET @index := '<section class="roster-section" aria-label="Bagian Kesekretariatan"><div class="roster-section-head"><h2>Bagian Kesekretariatan</h2><span class="roster-count">3 unit</span></div><ul class="unit-index"><li><a href="/profil-pengadilan/profil-kesekretariatan/subbagian-kepegawaian-ortala"><strong>Subbagian Kepegawaian, Organisasi, dan Tata Laksana</strong><span class="unit-index__officer">Candra Firmansyah, S.I.Pust.</span><span class="unit-index__lead">Mengelola data, dokumen, dan layanan administrasi kepegawaian.</span></a></li><li><a href="/profil-pengadilan/profil-kesekretariatan/subbagian-ptip"><strong>Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan</strong><span class="unit-index__officer">Muhammad Faris Akbar, A.Md.</span><span class="unit-index__lead">Menyusun rencana program, kegiatan, anggaran, dan dokumen perencanaan satuan kerja.</span></a></li><li><a href="/profil-pengadilan/profil-kesekretariatan/subbagian-umum-keuangan"><strong>Subbagian Umum dan Keuangan</strong><span class="unit-index__officer">Frans Alberto Siregar, S.T.</span><span class="unit-index__lead">Mengelola tata usaha, persuratan, kearsipan, perlengkapan, dan pemeliharaan fasilitas kantor.</span></a></li></ul></section>';
UPDATE #__content
SET introtext = CONCAT(LEFT(introtext, CHAR_LENGTH(introtext) - 6), @index, '</div>'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE alias = 'profil-kesekretariatan'
  AND introtext LIKE '%</div>'
  AND introtext NOT LIKE '%unit-index%';
