-- Tampilan cetak, feed, dan klien yang tidak memakai override bagan harus
-- menampilkan poster sumber yang sama; srcset lama menggambarkan susunan berbeda.
UPDATE #__content
SET introtext = REPLACE(REPLACE(REPLACE(introtext,
    ' srcset="/images/profil/struktur-organisasi-2026-480.webp?v=20261120-logo 480w, /images/profil/struktur-organisasi-2026-960.webp?v=20261120-logo 960w, /images/profil/struktur-organisasi-2026-1500.webp?v=20261120-logo 1500w, /images/profil/struktur-organisasi-2026.png?v=20261120-logo 2245w" sizes="(max-width: 760px) 76vw, (max-width: 1180px) 88vw, 1030px"', ''),
    '/images/profil/struktur-organisasi-2026.png?v=20261120-logo', '/images/profil/struktur-organisasi-poster-baru.png'),
    'width="2245" height="1587"', 'width="2244" height="1587"'),
    modified = UTC_TIMESTAMP()
WHERE id = 56 AND alias = 'struktur-organisasi'
  AND introtext LIKE '%/images/profil/struktur-organisasi-2026.png?v=20261120-logo%';
