-- Merge newly filled documents into the existing archive so their typography and spacing match sibling cards.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<section class="transparency-archive" aria-label="Dokumen Laporan Tahunan 2023"><a class="transparency-document" href="https://drive.google.com/file/d/1GiVeZP6asqHZuvWoFglr8JDZtYD3efkP/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Pelaksanaan Kegiatan Tahun 2023</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a></section><section class="transparency-archive" aria-label="Daftar dokumen">',
        '<section class="transparency-archive" aria-label="Daftar dokumen"><a class="transparency-document" href="https://drive.google.com/file/d/1GiVeZP6asqHZuvWoFglr8JDZtYD3efkP/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Pelaksanaan Kegiatan Tahun 2023</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>'
    ),
    modified = NOW()
WHERE alias = 'laporan-tahunan'
  AND introtext LIKE '%aria-label="Dokumen Laporan Tahunan 2023"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<section class="transparency-archive" aria-label="Dokumen Realisasi DIPA April 2026"><a class="transparency-document" href="https://drive.google.com/file/d/11ZHiHIDzlRdJnH8zFFeRoq9KmfisJi4j/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Realisasi Anggaran DIPA 01 dan 03 April 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a></section><section class="transparency-archive" aria-label="Daftar dokumen">',
        '<section class="transparency-archive" aria-label="Daftar dokumen"><a class="transparency-document" href="https://drive.google.com/file/d/11ZHiHIDzlRdJnH8zFFeRoq9KmfisJi4j/view?usp=sharing" target="_blank" rel="noopener"><span><strong>April 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>'
    ),
    modified = NOW()
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND introtext LIKE '%aria-label="Dokumen Realisasi DIPA April 2026"%';
