-- Force browsers holding the previous same-name thumbnail to request the current poster.
-- Keep the full-size lightbox URL cache-busted too; the underlying files remain canonical.
UPDATE #__modules
SET content = REPLACE(
        REPLACE(
            content,
            'data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp"',
            'data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp?v=20261103-maklumat"'
        ),
        '<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp"',
        '<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp?v=20261103-maklumat"'
    )
WHERE id = 808
  AND module = 'mod_custom'
  AND content LIKE '%data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp"%'
  AND content LIKE '%<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp"%';
