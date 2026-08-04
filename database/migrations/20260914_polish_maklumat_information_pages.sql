-- Refine Maklumat and Information Request pages with stable lightweight document previews.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            REPLACE(introtext,
                '<div class="svc-hero">',
                '<div class="svc-hero svc-hero--maklumat">'),
            '<blockquote>',
            '<blockquote class="svc-statement">'),
        '<button type="button" class="svc-zoom" data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp"',
        '<button type="button" class="svc-zoom svc-document-preview" data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp"'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='maklumat-pelayanan';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-pelayanan-2026.webp" alt="Maklumat Pelayanan Pengadilan Negeri Natuna" loading="lazy" decoding="async">',
        '<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp" width="226" height="320" alt="Pratinjau Maklumat Pelayanan Pengadilan Negeri Natuna" loading="eager" decoding="async">'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='maklumat-pelayanan';

UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            REPLACE(introtext,
                '<div class="svc-hero">',
                '<div class="svc-hero svc-hero--information">'),
            '<blockquote>',
            '<blockquote class="svc-statement">'),
        '<button type="button" class="svc-zoom" data-maklumat-zoom="/images/layanan/maklumat-layanan-informasi-publik.webp"',
        '<button type="button" class="svc-zoom svc-document-preview" data-maklumat-zoom="/images/layanan/maklumat-layanan-informasi-publik.webp"'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='permohonan-informasi';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-layanan-informasi-publik.webp" alt="Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">',
        '<img src="/images/layanan/maklumat-layanan-informasi-publik-thumb.webp" width="226" height="320" alt="Pratinjau Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='permohonan-informasi';
