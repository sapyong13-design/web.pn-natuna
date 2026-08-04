-- Normalize intrinsic metadata for the latest organization chart asset.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            introtext,
            'src="/images/profil/struktur-organisasi-2026.png" alt="Struktur organisasi Pengadilan Negeri Natuna" width="1500" height="1061"',
            'src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II"'
        ),
        'src="/images/profil/struktur-organisasi-2026.png" alt="Bagan struktur organisasi Pengadilan Negeri Natuna Kelas II" width="1500" height="1061"',
        'src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II"'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='struktur-organisasi'
  AND introtext LIKE '%/images/profil/struktur-organisasi-2026.png%';
