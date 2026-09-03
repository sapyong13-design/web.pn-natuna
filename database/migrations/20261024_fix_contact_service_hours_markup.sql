-- Normalize whitespace-tolerant contact-card markup left unchanged by the prior service-hours migration.
UPDATE #__content
SET
    introtext = REGEXP_REPLACE(
        introtext,
        '<strong>Jam layanan</strong>[[:space:]]*<span>Senin-Kamis 08.00-16.30 WIB</span>[[:space:]]*<span>Jumat 08.00-17.00 WIB</span>',
        '<strong>Jam layanan Pengadilan &amp; PTSP</strong><span>Senin s.d. Kamis: Jam Kerja 08.00&ndash;16.30 WIB; PTSP 08.00&ndash;16.00 WIB; Istirahat 12.00&ndash;13.00 WIB</span><span>Jumat: Jam Kerja 08.00&ndash;17.00 WIB; PTSP 08.00&ndash;16.30 WIB; Istirahat 12.00&ndash;13.30 WIB</span>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE id IN (9, 20, 29)
  AND introtext REGEXP '<strong>Jam layanan</strong>[[:space:]]*<span>Senin-Kamis 08.00-16.30 WIB</span>';
