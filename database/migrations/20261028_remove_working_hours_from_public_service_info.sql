-- Public-facing hours distinguish PTSP service and rest periods only.
UPDATE #__modules
SET content = '<div class="service-hours-card"><p class="service-hours-kicker">Pengadilan &amp; PTSP</p><p class="service-hours-status" data-service-status hidden aria-live="polite"><span aria-hidden="true"></span><strong>Memeriksa jam layanan</strong></p><dl class="service-hours"><div><dt>Senin&ndash;Kamis</dt><dd><span>Jam Pelayanan (PTSP)</span><strong>08.00&ndash;16.00 WIB</strong></dd><dd><span>Jam Istirahat</span><strong>12.00&ndash;13.00 WIB</strong></dd></div><div><dt>Jumat</dt><dd><span>Jam Pelayanan (PTSP)</span><strong>08.00&ndash;16.30 WIB</strong></dd><dd><span>Jam Istirahat</span><strong>12.00&ndash;13.30 WIB</strong></dd></div></dl><p class="service-hours-note">Layanan PTSP tutup selama jam istirahat.</p><a class="panel-link service-hours-action" href="/jenis-layanan-ptsp"><span>Lihat jenis layanan PTSP</span><span aria-hidden="true">&rarr;</span></a></div>'
WHERE id = 115
  AND position = 'home-service-info';

UPDATE #__content
SET
    introtext = REPLACE(
        introtext,
        '<strong>Jam layanan Pengadilan &amp; PTSP</strong><span>Senin s.d. Kamis: Jam Kerja 08.00&ndash;16.30 WIB; PTSP 08.00&ndash;16.00 WIB; Istirahat 12.00&ndash;13.00 WIB</span><span>Jumat: Jam Kerja 08.00&ndash;17.00 WIB; PTSP 08.00&ndash;16.30 WIB; Istirahat 12.00&ndash;13.30 WIB</span>',
        '<strong>Jam layanan PTSP</strong><span>Senin s.d. Kamis: 08.00&ndash;16.00 WIB; istirahat 12.00&ndash;13.00 WIB</span><span>Jumat: 08.00&ndash;16.30 WIB; istirahat 12.00&ndash;13.30 WIB</span>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE id IN (9, 20, 29)
  AND introtext LIKE '%Jam Kerja%';
