-- Pisahkan kartu "Kinerja & Akuntabilitas" jadi dua kartu terpisah.
--
-- Satu kartu memuat dua hal yang berbeda sifatnya: indeks survei (skor SKM/IPAK per
-- triwulan, di-refresh tools/refresh-survey.py) dan realisasi anggaran DIPA (donut per
-- bulan, di-refresh tools/refresh-dipa.py). Digabung, kartu sidebar itu jadi sangat
-- tinggi dan pembaca harus menggulir di dalam kartu untuk melihat blok kedua.
--
-- Setelah migrasi ini:
--   816 (home-survey) = "Kinerja"      -> hanya blok kartu skor survei
--   817 (home-dipa)   = "Akuntabilitas" -> hanya blok widget DIPA
--
-- Modul 817 sudah ada sejak lama sebagai modul DIPA mandiri yang tidak dipublikasikan;
-- sekarang diaktifkan kembali di posisi `home-dipa`, yang sudah di-include templat
-- tepat setelah `home-survey` (templates/pn_natuna_2026/index.php).
--
-- `tools/refresh-dipa.py` ikut dipindahkan ke modul 817. Kedua skrip refresh hanya
-- menyentuh bloknya sendiri (`.survey-widget` / `.dipa-widget`), jadi menjalankannya
-- berulang tetap idempoten.

-- --- 816: tinggal kartu skor survei ------------------------------------------------
UPDATE #__modules
SET title = 'Kinerja'
WHERE id = 816
  AND title = 'Kinerja & Akuntabilitas';

UPDATE #__modules
SET content = REPLACE(content, '<h2>Kinerja &amp; Akuntabilitas</h2>', '<h2>Kinerja</h2>')
WHERE id = 816
  AND content LIKE '%<h2>Kinerja &amp; Akuntabilitas</h2>%';

-- Potong blok DIPA dari konten 816. SUBSTRING_INDEX dipakai supaya pemotongan tidak
-- bergantung pada isi widget (yang ditulis ulang tiap cron). Penjaga `LIKE` membuat
-- replay tidak menyentuh konten yang sudah bersih.
UPDATE #__modules
SET content = SUBSTRING_INDEX(content, '<div class="dipa-widget"', 1)
WHERE id = 816
  AND content LIKE '%<div class="dipa-widget"%';

-- --- 817: kartu Akuntabilitas ------------------------------------------------------
UPDATE #__modules
SET title = 'Akuntabilitas',
    published = 1,
    content = REPLACE(content, '<h2>Realisasi Anggaran DIPA</h2>', '<h2>Akuntabilitas</h2>')
WHERE id = 817;

-- Penanda `data-dipa-board` dibaca JS pemilih periode. Versi lama blok ini belum
-- memilikinya; tanpa normalisasi, tab periode tidak berfungsi sampai cron berikutnya
-- menulis ulang widget.
UPDATE #__modules
SET content = REPLACE(content, '<div class="dipa-widget">', '<div class="dipa-widget" data-dipa-board>')
WHERE id = 817
  AND content LIKE '%<div class="dipa-widget">%';
