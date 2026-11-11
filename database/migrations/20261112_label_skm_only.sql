-- Label tile indeks di beranda cukup "SKM", bukan "SKM / IKM".
--
-- Keduanya nama resmi yang sama (Survei Kepuasan Masyarakat = Indeks Kepuasan
-- Masyarakat), dan menuliskan dua-duanya memakan ruang label pada tile sempit.
-- Nama lengkap tetap muncul di tooltip/aria dokumen lewat `data-maklumat-label`,
-- jadi tidak ada informasi yang hilang untuk pembaca maupun pembaca layar.
--
-- Literal saja, tanpa backreference: aman identik di MySQL 8 dan MariaDB. Guard
-- memastikan migrasi tidak menyentuh tile yang labelnya sudah diubah manual.
UPDATE #__modules
SET content = REPLACE(
        content,
        '<span class="survey-score-label">SKM / IKM</span>',
        '<span class="survey-score-label">SKM</span>'
    )
WHERE id = 816
  AND content LIKE '%<span class="survey-score-label">SKM / IKM</span>%';
