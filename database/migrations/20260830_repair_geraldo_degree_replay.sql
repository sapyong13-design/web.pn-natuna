-- Repair for 20260826_sync_geraldo_degree_and_latest_news.sql.
-- Migrasi itu menaikkan gelar Geraldo dengan substitusi teks biasa yang needle-nya
-- terkandung di dalam penggantinya, sehingga setiap pemutaran ulang menambah satu
-- akhiran gelar lagi. Berkas lamanya sudah diterapkan dan immutable (runner menolak
-- checksum berubah), jadi perbaikannya berupa normalisasi di sini: REGEXP_REPLACE
-- mengembalikan berapa pun akhiran gelar berulang menjadi tepat satu dan mengunci
-- lencana roster pada S2. Kedua pernyataan aman diputar ulang berkali-kali.

UPDATE #__content SET introtext=REGEXP_REPLACE(introtext,'Geraldo Gracelo Mario Situmeang, S\\.H\\.(, M\\.H\\.)*','Geraldo Gracelo Mario Situmeang, S.H., M.H.') WHERE alias='profil-hakim' AND introtext LIKE '%Geraldo Gracelo Mario Situmeang%';

UPDATE #__content SET introtext=REGEXP_REPLACE(introtext,'hakim/geraldo\\.jpeg" alt="Geraldo Gracelo Mario Situmeang, S\\.H\\., M\\.H\\." loading="lazy"><span class="roster-degree">S1','hakim/geraldo.jpeg" alt="Geraldo Gracelo Mario Situmeang, S.H., M.H." loading="lazy"><span class="roster-degree">S2') WHERE alias='profil-hakim' AND introtext LIKE '%hakim/geraldo.jpeg%';
