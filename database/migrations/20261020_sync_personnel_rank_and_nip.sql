-- Synchronize current personnel rank grades and Ardiansyah employee ID across every profile article.
-- Scoped by personnel name so unrelated historical article text remains unchanged.

UPDATE #__content
SET introtext = REPLACE(introtext, 'Penata Muda Tingkat I / III.b', 'Penata / III.c'),
    fulltext = REPLACE(fulltext, 'Penata Muda Tingkat I / III.b', 'Penata / III.c'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE (introtext LIKE '%Salihin Ardiansyah%' OR fulltext LIKE '%Salihin Ardiansyah%')
  AND (introtext LIKE '%Penata Muda Tingkat I / III.b%' OR fulltext LIKE '%Penata Muda Tingkat I / III.b%');

UPDATE #__content
SET introtext = REPLACE(introtext, 'Penata Muda / III.a', 'Penata Muda Tingkat I / III.b'),
    fulltext = REPLACE(fulltext, 'Penata Muda / III.a', 'Penata Muda Tingkat I / III.b'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE (
    introtext LIKE '%Alfariz Maulana Reza%'
    OR introtext LIKE '%Geraldo Gracelo Mario Situmeang%'
    OR introtext LIKE '%Haditio%'
    OR introtext LIKE '%Swandi Hutabarat%'
    OR fulltext LIKE '%Alfariz Maulana Reza%'
    OR fulltext LIKE '%Geraldo Gracelo Mario Situmeang%'
    OR fulltext LIKE '%Haditio%'
    OR fulltext LIKE '%Swandi Hutabarat%'
)
  AND (introtext LIKE '%Penata Muda / III.a%' OR fulltext LIKE '%Penata Muda / III.a%');

UPDATE #__content
SET introtext = REPLACE(introtext, 'Pengatur / II.c', 'Pengatur Tingkat I / II.d'),
    fulltext = REPLACE(fulltext, 'Pengatur / II.c', 'Pengatur Tingkat I / II.d'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE (introtext LIKE '%Ari Putra Utama%' OR fulltext LIKE '%Ari Putra Utama%')
  AND (introtext LIKE '%Pengatur / II.c%' OR fulltext LIKE '%Pengatur / II.c%');

UPDATE #__content
SET introtext = REPLACE(introtext, '1990010620252101027', '199001062025211027'),
    fulltext = REPLACE(fulltext, '1990010620252101027', '199001062025211027'),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE (introtext LIKE '%Ardiansyah%' OR fulltext LIKE '%Ardiansyah%')
  AND (introtext LIKE '%1990010620252101027%' OR fulltext LIKE '%1990010620252101027%');
