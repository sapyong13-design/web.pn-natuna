-- Polish Kesekretariatan unit copy for concise, parallel, readable service descriptions.

UPDATE #__content SET introtext=REPLACE(REPLACE(REPLACE(REPLACE(introtext,
'Subbagian Kepegawaian, Organisasi, dan Tata Laksana mendukung pengelolaan administrasi kepegawaian, organisasi, tata laksana, dan pengembangan aparatur Pengadilan Negeri Natuna.',
'Subbagian Kepegawaian, Organisasi, dan Tata Laksana mengelola administrasi dan pengembangan aparatur serta mendukung penataan organisasi di Pengadilan Negeri Natuna.'),
'Pengelolaan administrasi kepegawaian, data aparatur, dan dokumentasi layanan kepegawaian.',
'Mengelola data, dokumen, dan layanan administrasi kepegawaian.'),
'Penataan organisasi, analisis jabatan, tata laksana, dan dukungan pengembangan kompetensi aparatur.',
'Mendukung penataan organisasi, analisis jabatan, tata laksana, dan pengembangan kompetensi aparatur.'),
'Pelaksanaan administrasi disiplin, kenaikan pangkat, cuti, pensiun, dan layanan kepegawaian sesuai ketentuan.',
'Memproses administrasi disiplin, kenaikan pangkat, cuti, dan pensiun sesuai ketentuan.'),modified=UTC_TIMESTAMP(),modified_by=0 WHERE alias='subbagian-kepegawaian-ortala';

UPDATE #__content SET introtext=REPLACE(REPLACE(REPLACE(REPLACE(introtext,
'Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP) mendukung perencanaan program dan anggaran, pengelolaan teknologi informasi, serta penyusunan laporan Pengadilan Negeri Natuna.',
'Subbagian Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP) mengelola perencanaan, layanan teknologi informasi, dan pelaporan di Pengadilan Negeri Natuna.'),
'Penyusunan rencana program, kegiatan, kebutuhan anggaran, dan dokumen perencanaan satuan kerja.',
'Menyusun rencana program, kegiatan, anggaran, dan dokumen perencanaan satuan kerja.'),
'Pengelolaan teknologi informasi, sistem informasi, jaringan, perangkat, dan dukungan layanan digital pengadilan.',
'Mengelola sistem informasi, jaringan, perangkat, dan layanan digital pengadilan.'),
'Pengumpulan data, pemantauan capaian, evaluasi, serta penyusunan laporan pelaksanaan kegiatan.',
'Mengumpulkan data, memantau capaian, mengevaluasi kegiatan, dan menyusun laporan.'),modified=UTC_TIMESTAMP(),modified_by=0 WHERE alias='subbagian-ptip';

UPDATE #__content SET introtext=REPLACE(REPLACE(REPLACE(REPLACE(introtext,
'Subbagian Umum dan Keuangan mendukung tata usaha, perlengkapan, rumah tangga, persuratan, serta pengelolaan keuangan Pengadilan Negeri Natuna.',
'Subbagian Umum dan Keuangan mengelola layanan umum, fasilitas kantor, keuangan, dan barang milik negara di Pengadilan Negeri Natuna.'),
'Pengelolaan tata usaha, persuratan, kearsipan, perlengkapan, rumah tangga, dan pemeliharaan fasilitas kantor.',
'Mengelola tata usaha, persuratan, kearsipan, perlengkapan, dan pemeliharaan fasilitas kantor.'),
'Pelaksanaan administrasi keuangan, perbendaharaan, pembayaran, pembukuan, dan pertanggungjawaban anggaran.',
'Melaksanakan perbendaharaan, pembayaran, pembukuan, dan pertanggungjawaban anggaran.'),
'Pengelolaan barang milik negara, kebutuhan operasional, keamanan, kebersihan, dan dukungan layanan umum.',
'Mengelola barang milik negara, kebutuhan operasional, keamanan, kebersihan, dan layanan umum.'),modified=UTC_TIMESTAMP(),modified_by=0 WHERE alias='subbagian-umum-keuangan';
