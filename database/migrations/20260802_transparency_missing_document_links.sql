-- Fill two previously missing transparency documents with user-provided Google Drive files.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<div class="transparency-status is-missing"><strong>2023</strong><span>Belum tersedia pada portal</span></div>',
        '<section class="transparency-archive" aria-label="Dokumen Laporan Tahunan 2023"><a class="transparency-document" href="https://drive.google.com/file/d/1GiVeZP6asqHZuvWoFglr8JDZtYD3efkP/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Pelaksanaan Kegiatan Tahun 2023</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a></section>'
    ),
    modified = NOW()
WHERE alias = 'laporan-tahunan'
  AND introtext LIKE '%<div class="transparency-status is-missing"><strong>2023</strong><span>Belum tersedia pada portal</span></div>%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<div class="transparency-status is-missing"><strong>April 2026</strong><span>Belum tersedia pada portal</span></div>',
        '<section class="transparency-archive" aria-label="Dokumen Realisasi DIPA April 2026"><a class="transparency-document" href="https://drive.google.com/file/d/11ZHiHIDzlRdJnH8zFFeRoq9KmfisJi4j/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Realisasi Anggaran DIPA 01 dan 03 April 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a></section>'
    ),
    modified = NOW()
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND introtext LIKE '%<div class="transparency-status is-missing"><strong>April 2026</strong><span>Belum tersedia pada portal</span></div>%';
