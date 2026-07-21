-- Keep transparency archives in descending chronological order.
SET @annual_2023 := '<a class="transparency-document" href="https://drive.google.com/file/d/1GiVeZP6asqHZuvWoFglr8JDZtYD3efkP/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Laporan Pelaksanaan Kegiatan Tahun 2023</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @annual_2022_marker := '<a class="transparency-document" href="https://drive.google.com/file/d/1UUEKzPfmOBomzRKOv0Kx0PJq1693rM4F/view?usp=drive_link"';
UPDATE #__content
SET introtext = REPLACE(introtext, @annual_2023, '')
WHERE alias = 'laporan-tahunan' AND LOCATE(@annual_2023, introtext) > 0;
UPDATE #__content
SET introtext = REPLACE(introtext, @annual_2022_marker, CONCAT(@annual_2023, @annual_2022_marker)), modified = NOW()
WHERE alias = 'laporan-tahunan'
  AND LOCATE(@annual_2023, introtext) = 0
  AND LOCATE(@annual_2022_marker, introtext) > 0;

SET @dipa_april := '<a class="transparency-document" href="https://drive.google.com/file/d/11ZHiHIDzlRdJnH8zFFeRoq9KmfisJi4j/view?usp=sharing" target="_blank" rel="noopener"><span><strong>April 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>';
SET @dipa_march_marker := '<a class="transparency-document" href="https://drive.google.com/file/d/1Qd4Rp913X94PPT2dQSpxuoq0TceU2DWC/view?usp=sharing"';
UPDATE #__content
SET introtext = REPLACE(introtext, @dipa_april, '')
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03' AND LOCATE(@dipa_april, introtext) > 0;
UPDATE #__content
SET introtext = REPLACE(introtext, @dipa_march_marker, CONCAT(@dipa_april, @dipa_march_marker)), modified = NOW()
WHERE alias = 'laporan-realisasi-anggaran-dipa-01-dan-03'
  AND LOCATE(@dipa_april, introtext) = 0
  AND LOCATE(@dipa_march_marker, introtext) > 0;
