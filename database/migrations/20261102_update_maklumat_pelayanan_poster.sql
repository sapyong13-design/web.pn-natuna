-- Point active Maklumat Pelayanan content at the current signed poster.
-- Repair the stale PNG URL, restore responsive candidates, and keep the current
-- chief-judge attribution aligned with the poster. Historical news is untouched.
UPDATE #__content
SET introtext = REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    introtext,
                    '<button type="button" class="svc-zoom" data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.png" data-maklumat-label="Maklumat Pelayanan Pengadilan Negeri Natuna" aria-label="Perbesar dokumen maklumat pelayanan">',
                    '<button type="button" class="svc-zoom svc-document-preview" data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp" data-maklumat-label="Maklumat Pelayanan Pengadilan Negeri Natuna" aria-label="Perbesar dokumen maklumat pelayanan">'
                ),
                'data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.png"',
                'data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp"'
            ),
            '<img src="/images/layanan/maklumat-pelayanan-2026.png" alt="Maklumat Pelayanan Pengadilan Negeri Natuna" loading="lazy" decoding="async">',
            '<img src="/images/layanan/maklumat-pelayanan-2026.webp" srcset="/images/layanan/maklumat-pelayanan-2026-480.webp 480w, /images/layanan/maklumat-pelayanan-2026-800.webp 800w, /images/layanan/maklumat-pelayanan-2026-1200.webp 1200w, /images/layanan/maklumat-pelayanan-2026.webp 1109w" sizes="(max-width: 760px) 76vw, 420px" width="1109" height="1568" alt="Maklumat Pelayanan Pengadilan Negeri Natuna" loading="lazy" decoding="async">'
        ),
        '<footer>Joko Ciptanto, S.H., M.H. &#8212; Wakil Ketua Pengadilan Negeri Natuna</footer>',
        '<footer>Joko Ciptanto, S.H., M.H. &#8212; Ketua Pengadilan Negeri Natuna</footer>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'maklumat-pelayanan'
  AND (
      introtext LIKE '%maklumat-pelayanan-2026.png%'
      OR introtext LIKE '%Wakil Ketua Pengadilan Negeri Natuna%'
  );
