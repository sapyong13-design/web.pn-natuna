-- Nyalakan pencatatan kueri pencarian.
--
-- Tabel `#__finder_logging` kosong dan parameter `gather_search_statistics` tidak pernah
-- diset, sehingga tidak ada satu pun data tentang apa yang benar-benar diketik warga.
-- Seluruh keputusan relevansi sejauh ini - termasuk daftar kata kunci di
-- `templates/pn_natuna_2026/data/sistem-daring.json` - berdiri di atas tebakan terdidik.
-- Dengan ini menyala, iterasi berikutnya bisa berdiri di atas bukti: kueri apa yang
-- sering diketik, dan mana yang pulang dengan nol hasil.
--
-- Yang dicatat hanya kata kuncinya, jumlah pencarian, dan jumlah hasil - tidak ada
-- alamat IP, tidak ada identitas. Aman terhadap kebijakan privasi situs.
SET @finder_params := (SELECT params FROM #__extensions WHERE element='com_finder' AND type='component' LIMIT 1);
UPDATE #__extensions
SET params = JSON_SET(
        CASE WHEN JSON_VALID(@finder_params) AND @finder_params <> '' THEN @finder_params ELSE '{}' END,
        '$.gather_search_statistics', '1'
    )
WHERE element='com_finder' AND type='component'
  AND (
        NOT JSON_VALID(@finder_params)
        OR @finder_params = ''
        OR JSON_UNQUOTE(JSON_EXTRACT(@finder_params, '$.gather_search_statistics')) IS NULL
        OR JSON_UNQUOTE(JSON_EXTRACT(@finder_params, '$.gather_search_statistics')) <> '1'
      );
