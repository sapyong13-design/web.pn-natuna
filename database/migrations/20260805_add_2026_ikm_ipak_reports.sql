-- Add complete 2026 quarterly IKM and IPAK reports from the public survey Drive folder.
SET @ikm_tw2 := '<a class="transparency-document" href="https://drive.google.com/file/d/1xC-S3C1KcO281gDfDtB3uJirr4ERQgD1/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan IKM Triwulan II 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @ikm_tw1 := '<a class="transparency-document" href="https://drive.google.com/file/d/1KXNIj-aiq7lERKUz-mx6FLUlVgdsefSF/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan IKM Triwulan I 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @ikm_archive := '<section class="transparency-archive" aria-label="Daftar dokumen">';
UPDATE #__content
SET introtext = REPLACE(introtext, @ikm_archive, CONCAT(@ikm_archive, @ikm_tw2, @ikm_tw1)), modified = NOW()
WHERE alias = 'laporan-skm'
  AND LOCATE(@ikm_tw2, introtext) = 0
  AND LOCATE(@ikm_tw1, introtext) = 0
  AND LOCATE(@ikm_archive, introtext) > 0;

SET @ipak_tw2 := '<a class="transparency-document" href="https://drive.google.com/file/d/1bpuyhlkUqIUf_g-d4A-XixXuCAWTottY/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan IPAK Triwulan II 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @ipak_tw1 := '<a class="transparency-document" href="https://drive.google.com/file/d/1zNta5MVeHZTl7WA-BYqHop2GpWPGLSxx/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan IPAK Triwulan I 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @ipak_archive := '<section class="transparency-archive" aria-label="Daftar dokumen">';
UPDATE #__content
SET introtext = REPLACE(introtext, @ipak_archive, CONCAT(@ipak_archive, @ipak_tw2, @ipak_tw1)), modified = NOW()
WHERE alias = 'laporan-spak'
  AND LOCATE(@ipak_tw2, introtext) = 0
  AND LOCATE(@ipak_tw1, introtext) = 0
  AND LOCATE(@ipak_archive, introtext) > 0;

-- Use one public-facing term consistently while preserving the existing route alias.
UPDATE #__content
SET title = 'Laporan IPAK',
    introtext = REPLACE(REPLACE(REPLACE(introtext,
        '<h2>Laporan SPAK</h2>', '<h2>Laporan IPAK</h2>'),
        'Informasi resmi laporan spak ', 'Informasi resmi laporan IPAK '),
        '<strong>Laporan SPAK ', '<strong>Laporan IPAK '),
    modified = NOW()
WHERE alias = 'laporan-spak';
