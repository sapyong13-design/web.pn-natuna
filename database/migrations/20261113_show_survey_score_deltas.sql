-- Tampilkan kenaikan tiap indeks pada tile Kinerja & Akuntabilitas.
--
-- Tile hanya memuat angka periode berjalan, sehingga pengunjung tidak tahu apakah
-- 4,00 itu naik, turun, atau tetap. Delta ditulis di markup seperti widget DIPA yang
-- sudah memakai pola sama (`dipa-delta is-up/is-flat`), lengkap dengan nilai pembanding
-- di atribut title agar angkanya bisa ditelusuri.
--
-- Urutan REPLACE penting: pola meta+penutup tile dipakai dua kali (SKM dan IPAK) dan
-- teksnya identik. Penggantian pertama memakai jangkar yang menyertakan pembuka tile
-- IPAK, sehingga pola polos yang tersisa hanya milik tile IPAK.
--
-- Penjaga `NOT LIKE '%survey-score-delta%'` membuat replay tidak berlipat.
UPDATE #__modules
SET content = REPLACE(
        REPLACE(
            REPLACE(content,
                '<span class="survey-score-meta">100,00% &middot; 49 responden</span></button><button type="button" class="survey-score-tile" data-maklumat-zoom="/images/surveys/IPAK_TW3_2026.png"',
                '<span class="survey-score-meta">100,00% &middot; 49 responden</span><span class="survey-score-delta is-up" title="Naik 0,03 poin dari 3,97 pada TW2 2026 (61 responden)"><span aria-hidden="true">&#9650;</span> +0,03 poin</span></button><button type="button" class="survey-score-tile" data-maklumat-zoom="/images/surveys/IPAK_TW3_2026.png"'),
            '<span class="survey-score-meta">100,00% &middot; 49 responden</span></button></div>',
            '<span class="survey-score-meta">100,00% &middot; 49 responden</span><span class="survey-score-delta is-flat" title="Tetap 4,00 seperti TW2 2026 (61 responden)">stabil vs TW2</span></button></div>'),
        'Indeks periode TW3 2026 (Juli&ndash;September). Klik kartu skor untuk dokumen publikasi.',
        'Indeks TW3 2026 (Juli&ndash;September) dibanding TW2 2026. Klik kartu skor untuk dokumen publikasi.')
WHERE id = 816
  AND content LIKE '%SKM_TW3_2026.png%'
  AND content NOT LIKE '%survey-score-delta%';
