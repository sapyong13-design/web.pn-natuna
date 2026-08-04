-- Apply the Maklumat polish to the active article behind /permohonan-informasi.
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
WHERE alias='prosedur-permohonan-informasi';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/layanan/maklumat-layanan-informasi-publik.webp" alt="Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">',
        '<img src="/images/layanan/maklumat-layanan-informasi-publik-thumb.webp" width="226" height="320" alt="Pratinjau Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async">'
    ),
    modified=UTC_TIMESTAMP(), modified_by=0
WHERE alias='prosedur-permohonan-informasi';
