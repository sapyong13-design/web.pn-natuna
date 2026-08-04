-- Use full-resolution WebP assets for sharp document previews.
-- Explicit intrinsic dimensions keep layout stable before image decoding completes.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp" width="226" height="320" alt="Pratinjau Maklumat Pelayanan Pengadilan Negeri Natuna" loading="eager" decoding="async">',
        '<img src="/images/layanan/maklumat-pelayanan-2026.webp" width="1240" height="1754" alt="Pratinjau Maklumat Pelayanan Pengadilan Negeri Natuna" loading="eager" decoding="async">'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='maklumat-pelayanan';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-layanan-informasi-publik-thumb.webp" width="226" height="320" alt="Pratinjau Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">',
        '<img src="/images/layanan/maklumat-layanan-informasi-publik.webp" width="1240" height="1754" alt="Pratinjau Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='prosedur-permohonan-informasi';
