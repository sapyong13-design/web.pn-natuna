-- Polish homepage service-hours card while preserving official operating hours and route.
UPDATE #__modules
SET content = '<div class="service-hours-card"><p class="service-hours-status" data-service-status hidden aria-live="polite"><span aria-hidden="true"></span><strong>Memeriksa jam layanan</strong></p><dl class="service-hours"><div><dt>Senin&ndash;Kamis</dt><dd>08.00&ndash;16.30 WIB</dd></div><div><dt>Jumat</dt><dd>08.00&ndash;17.00 WIB</dd></div></dl><p class="service-hours-break"><span>Waktu istirahat</span><strong>Menyesuaikan ketentuan kantor</strong></p><p class="service-hours-note">Layanan PTSP tersedia setiap hari kerja.</p><a class="panel-link service-hours-action" href="/jenis-layanan-ptsp"><span>Lihat jenis layanan PTSP</span><span aria-hidden="true">&rarr;</span></a></div>'
WHERE id = 115
  AND position = 'home-service-info';
