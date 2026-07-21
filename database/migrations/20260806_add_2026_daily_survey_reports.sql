-- Add January-June 2026 daily survey reports, newest first, using existing monthly card labels.
SET @daily_2026 := CONCAT(
'<a class="transparency-document" href="https://drive.google.com/file/d/1wP9wwKSkdnHJWSCyr6Sfr5s1vnpmwsrf/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Juni 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>',
'<a class="transparency-document" href="https://drive.google.com/file/d/1_gLxiqKNkYgoLemicAFjKq2oGzaGtyJS/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Mei 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>',
'<a class="transparency-document" href="https://drive.google.com/file/d/1Px1KtgFxxMX_xuzA2-AefdV_WBODqsmY/view?usp=sharing" target="_blank" rel="noopener"><span><strong>April 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>',
'<a class="transparency-document" href="https://drive.google.com/file/d/13v6Tvn2fFIYSXUtw1znsRedh84Gt59-u/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Maret 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>',
'<a class="transparency-document" href="https://drive.google.com/file/d/1PXFdPJGHzzPlIELYlRH7pnF16PYXN4sX/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Februari 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>',
'<a class="transparency-document" href="https://drive.google.com/file/d/1jrHHx83or51I4vIJbZ-FiciHW3pvD2kv/view?usp=sharing" target="_blank" rel="noopener"><span><strong>Januari 2026</strong><small>Google Drive &mdash; buka di tab baru</small></span><span aria-hidden="true">&nearr;</span></a>'
);
SET @daily_archive := '<section class="transparency-archive" aria-label="Daftar dokumen">';
UPDATE #__content
SET introtext = REPLACE(introtext, @daily_archive, CONCAT(@daily_archive, @daily_2026)), modified = NOW()
WHERE alias = 'laporan-survei-harian'
  AND LOCATE('1wP9wwKSkdnHJWSCyr6Sfr5s1vnpmwsrf', introtext) = 0
  AND LOCATE(@daily_archive, introtext) > 0;
