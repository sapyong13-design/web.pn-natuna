-- Publish the latest 2026 organization chart with stable intrinsic dimensions.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            introtext,
            '/images/profil/struktur-organisasi.png',
            '/images/profil/struktur-organisasi-2026.png'
        ),
        '<img src="/images/profil/struktur-organisasi-2026.png" alt="Bagan struktur organisasi Pengadilan Negeri Natuna Kelas II"',
        '<img src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II"'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='struktur-organisasi'
  AND introtext LIKE '%/images/profil/struktur-organisasi.png%';

UPDATE #__content
SET `fulltext` = REPLACE(
        REPLACE(
            `fulltext`,
            '/images/profil/struktur-organisasi.png',
            '/images/profil/struktur-organisasi-2026.png'
        ),
        '<img src="/images/profil/struktur-organisasi-2026.png" alt="Bagan struktur organisasi Pengadilan Negeri Natuna Kelas II"',
        '<img src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II"'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='struktur-organisasi'
  AND `fulltext` LIKE '%/images/profil/struktur-organisasi.png%';
