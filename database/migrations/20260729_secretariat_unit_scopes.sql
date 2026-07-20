-- Add concise service scopes to Kesekretariatan unit profiles, matching Kepaniteraan unit structure.

UPDATE #__content
SET introtext=CONCAT(introtext,'<section class="roster-section"><div class="roster-section-head"><h2>Ruang Lingkup Layanan</h2></div><ul class="unit-scope"><li>Pengelolaan administrasi kepegawaian, data aparatur, dan dokumentasi layanan kepegawaian.</li><li>Penataan organisasi, analisis jabatan, tata laksana, dan dukungan pengembangan kompetensi aparatur.</li><li>Pelaksanaan administrasi disiplin, kenaikan pangkat, cuti, pensiun, dan layanan kepegawaian sesuai ketentuan.</li></ul></section>'),modified=UTC_TIMESTAMP(),modified_by=0
WHERE alias='subbagian-kepegawaian-ortala' AND introtext NOT LIKE '%<h2>Ruang Lingkup Layanan</h2>%';

UPDATE #__content
SET introtext=CONCAT(introtext,'<section class="roster-section"><div class="roster-section-head"><h2>Ruang Lingkup Layanan</h2></div><ul class="unit-scope"><li>Penyusunan rencana program, kegiatan, kebutuhan anggaran, dan dokumen perencanaan satuan kerja.</li><li>Pengelolaan teknologi informasi, sistem informasi, jaringan, perangkat, dan dukungan layanan digital pengadilan.</li><li>Pengumpulan data, pemantauan capaian, evaluasi, serta penyusunan laporan pelaksanaan kegiatan.</li></ul></section>'),modified=UTC_TIMESTAMP(),modified_by=0
WHERE alias='subbagian-ptip' AND introtext NOT LIKE '%<h2>Ruang Lingkup Layanan</h2>%';

UPDATE #__content
SET introtext=CONCAT(introtext,'<section class="roster-section"><div class="roster-section-head"><h2>Ruang Lingkup Layanan</h2></div><ul class="unit-scope"><li>Pengelolaan tata usaha, persuratan, kearsipan, perlengkapan, rumah tangga, dan pemeliharaan fasilitas kantor.</li><li>Pelaksanaan administrasi keuangan, perbendaharaan, pembayaran, pembukuan, dan pertanggungjawaban anggaran.</li><li>Pengelolaan barang milik negara, kebutuhan operasional, keamanan, kebersihan, dan dukungan layanan umum.</li></ul></section>'),modified=UTC_TIMESTAMP(),modified_by=0
WHERE alias='subbagian-umum-keuangan' AND introtext NOT LIKE '%<h2>Ruang Lingkup Layanan</h2>%';
