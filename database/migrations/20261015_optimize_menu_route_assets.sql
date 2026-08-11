-- Reduce first-view payload on heavy menu routes without changing full-resolution downloads.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II" loading="eager" decoding="async">',
        '<img src="/images/profil/struktur-organisasi-2026.png" srcset="/images/profil/struktur-organisasi-2026-480.webp 480w, /images/profil/struktur-organisasi-2026-960.webp 960w, /images/profil/struktur-organisasi-2026-1500.webp 1500w, /images/profil/struktur-organisasi-2026.png 2245w" sizes="(max-width: 760px) 76vw, (max-width: 1180px) 88vw, 1030px" width="2245" height="1587" alt="Bagan struktur organisasi terbaru Pengadilan Negeri Natuna Kelas II" loading="lazy" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'struktur-organisasi'
  AND introtext LIKE '%<img src="/images/profil/struktur-organisasi-2026.png" width="2245" height="1587"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-pelayanan-2026.webp" width="1240" height="1754" alt="Pratinjau Maklumat Pelayanan Pengadilan Negeri Natuna" loading="eager" decoding="async">',
        '<img src="/images/layanan/maklumat-pelayanan-2026.webp" srcset="/images/layanan/maklumat-pelayanan-2026-480.webp 480w, /images/layanan/maklumat-pelayanan-2026-800.webp 800w, /images/layanan/maklumat-pelayanan-2026-1200.webp 1200w, /images/layanan/maklumat-pelayanan-2026.webp 1414w" sizes="(max-width: 760px) 76vw, 420px" width="1414" height="2000" alt="Pratinjau Maklumat Pelayanan Pengadilan Negeri Natuna" loading="lazy" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'maklumat-pelayanan'
  AND introtext LIKE '%<img src="/images/layanan/maklumat-pelayanan-2026.webp" width="1240" height="1754"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/hero/gedung-pn-natuna-2026.webp" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1536" height="1024" loading="eager" decoding="async">',
        '<img src="/images/hero/gedung-pn-natuna-2026.webp" srcset="/images/hero/gedung-pn-natuna-2026-400.webp 400w, /images/hero/gedung-pn-natuna-2026-800.webp 800w, /images/hero/gedung-pn-natuna-2026-1200.webp 1200w, /images/hero/gedung-pn-natuna-2026.webp 1536w" sizes="(max-width: 760px) 82vw, 440px" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1536" height="1024" loading="eager" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'sejarah-pengadilan'
  AND introtext LIKE '%<img src="/images/hero/gedung-pn-natuna-2026.webp" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1536"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg" alt="Joko Ciptanto, S.H., M.H., Wakil Ketua Pengadilan Negeri Natuna" width="600" height="800" loading="eager" decoding="async">',
        '<img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg" srcset="/images/profil/pegawai/hakim/joko-ciptanto-400.webp 400w, /images/profil/pegawai/hakim/joko-ciptanto-800.webp 800w, /images/profil/pegawai/hakim/joko-ciptanto.jpg 900w" sizes="(max-width: 760px) 83vw, 402px" alt="Joko Ciptanto, S.H., M.H., Wakil Ketua Pengadilan Negeri Natuna" width="900" height="990" loading="eager" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'kata-sambutan'
  AND introtext LIKE '%<img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg" alt="Joko Ciptanto, S.H., M.H., Wakil Ketua Pengadilan Negeri Natuna" width="600"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/berita/2026-briefing-ptsp-1.jpeg" alt="Briefing pelayanan PTSP Pengadilan Negeri Natuna" width="1152" height="864" loading="eager" decoding="async">',
        '<img src="/images/berita/2026-briefing-ptsp-1.jpeg" srcset="/images/berita/2026-briefing-ptsp-1-400.webp 400w, /images/berita/2026-briefing-ptsp-1-800.webp 800w, /images/berita/2026-briefing-ptsp-1.jpeg 1152w" sizes="(max-width: 760px) 70vw, 338px" alt="Briefing pelayanan PTSP Pengadilan Negeri Natuna" width="1152" height="864" loading="eager" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'tugas-pokok-fungsi'
  AND introtext LIKE '%<img src="/images/berita/2026-briefing-ptsp-1.jpeg" alt="Briefing pelayanan PTSP Pengadilan Negeri Natuna" width="1152"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/gallery/lokasi-kantor-2026.webp" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1200" height="800" loading="eager" decoding="async">',
        '<img src="/images/layanan/gallery/lokasi-kantor-2026.webp" srcset="/images/layanan/gallery/lokasi-kantor-2026-400.webp 400w, /images/layanan/gallery/lokasi-kantor-2026-800.webp 800w, /images/layanan/gallery/lokasi-kantor-2026.webp 1200w" sizes="(max-width: 760px) 70vw, 358px" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1200" height="800" loading="eager" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'visi-dan-misi-pengadilan-negeri-natuna'
  AND introtext LIKE '%<img src="/images/layanan/gallery/lokasi-kantor-2026.webp" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1200"%';

-- Browser-native lazy loading fetches embeds far before they are visible. Keep the
-- existing IntersectionObserver path in control by withholding src until 400px away.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            introtext,
            '<iframe title="Peta lokasi Pengadilan Negeri Natuna" data-src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed" loading="lazy"',
            '<iframe title="Peta lokasi Pengadilan Negeri Natuna" data-embed-src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed" loading="lazy"'
        ),
        '<iframe title="Peta lokasi Pengadilan Negeri Natuna" src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed" loading="lazy"',
        '<iframe title="Peta lokasi Pengadilan Negeri Natuna" data-embed-src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed" loading="lazy"'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'kontak-landing'
  AND (introtext LIKE '%<iframe title="Peta lokasi Pengadilan Negeri Natuna" data-src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed"%'
       OR introtext LIKE '%<iframe title="Peta lokasi Pengadilan Negeri Natuna" src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&output=embed"%');

UPDATE #__content
SET introtext = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(
                introtext,
                '<img src="/images/social/whatsapp.svg" alt="">',
                '<img src="/images/social/whatsapp.svg" alt="" width="30" height="30" loading="lazy" decoding="async">'
            ),
            '<img src="/images/social/instagram.svg" alt="">',
            '<img src="/images/social/instagram.svg" alt="" width="30" height="30" loading="lazy" decoding="async">'
        ),
        '<img src="/images/social/facebook.svg" alt="">',
        '<img src="/images/social/facebook.svg" alt="" width="30" height="30" loading="lazy" decoding="async">'
    ),
    '<img src="/images/social/youtube.svg" alt="">',
    '<img src="/images/social/youtube.svg" alt="" width="30" height="30" loading="lazy" decoding="async">'
),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'kontak-landing'
  AND (introtext LIKE '%<img src="/images/social/whatsapp.svg" alt="">%'
       OR introtext LIKE '%<img src="/images/social/instagram.svg" alt="">%'
       OR introtext LIKE '%<img src="/images/social/facebook.svg" alt="">%'
       OR introtext LIKE '%<img src="/images/social/youtube.svg" alt="">%');

-- Header and footer assets occur on every menu route.
UPDATE #__modules
SET content = REPLACE(
        content,
        '<img class="brand-mark" src="/images/brand/logo-pn-natuna.webp" alt="Logo Pengadilan Negeri Natuna" width="72" height="72" loading="eager">',
        '<img class="brand-mark" src="/images/brand/logo-pn-natuna.webp" srcset="/images/brand/logo-pn-natuna-96.webp 96w, /images/brand/logo-pn-natuna.webp 179w" sizes="(max-width: 760px) 42px, 72px" alt="Logo Pengadilan Negeri Natuna" width="72" height="72" loading="eager">'
    )
WHERE position = 'header-brand'
  AND module = 'mod_custom'
  AND content LIKE '%<img class="brand-mark" src="/images/brand/logo-pn-natuna.webp" alt="Logo Pengadilan Negeri Natuna"%';

UPDATE #__modules
SET content = REPLACE(
    REPLACE(
        REPLACE(
            content,
            '<img src="/images/social/instagram.svg" alt="Instagram" loading="lazy" decoding="async">',
            '<img src="/images/social/instagram.svg" alt="Instagram" width="24" height="24" loading="lazy" decoding="async">'
        ),
        '<img src="/images/social/facebook.svg" alt="Facebook" loading="lazy" decoding="async">',
        '<img src="/images/social/facebook.svg" alt="Facebook" width="24" height="24" loading="lazy" decoding="async">'
    ),
    '<img src="/images/social/youtube.svg" alt="YouTube" loading="lazy" decoding="async">',
    '<img src="/images/social/youtube.svg" alt="YouTube" width="24" height="24" loading="lazy" decoding="async">'
)
WHERE position = 'footer-social'
  AND module = 'mod_custom'
  AND (content LIKE '%<img src="/images/social/instagram.svg" alt="Instagram" loading="lazy"%'
       OR content LIKE '%<img src="/images/social/facebook.svg" alt="Facebook" loading="lazy"%'
       OR content LIKE '%<img src="/images/social/youtube.svg" alt="YouTube" loading="lazy"%');
