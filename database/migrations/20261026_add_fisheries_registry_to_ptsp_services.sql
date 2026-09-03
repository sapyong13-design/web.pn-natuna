-- Complete the PTSP registry-service list with the existing Kepaniteraan Khusus Perikanan unit.
UPDATE #__content
SET introtext = REPLACE(
    introtext,
    '<li>Layanan legalisasi, riset, dan informasi hukum yang tersedia di pengadilan.</li></ul></div></div><h2>Dokumen Informasi Layanan</h2>',
    '<li>Layanan legalisasi, riset, dan informasi hukum yang tersedia di pengadilan.</li></ul></div><div class="svc-card"><span class="svc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h18l-2 11H5L3 7z"/><path d="M7 7c0-2 1.8-4 5-4s5 2 5 4"/><path d="M8 13c1.2 1 2.5 1.5 4 1.5s2.8-.5 4-1.5"/></svg></span><h3>Kepaniteraan Khusus Perikanan</h3><ul><li>Administrasi penerimaan dan pencatatan perkara perikanan.</li><li>Pelayanan informasi perkara perikanan melalui PTSP dan sistem resmi yang berlaku.</li><li>Dukungan administrasi persidangan serta penyelesaian perkara perikanan.</li></ul><a class="svc-link" href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-khusus-perikanan">Lihat layanan Kepaniteraan Khusus Perikanan <span aria-hidden="true">&rarr;</span></a></div></div><h2>Dokumen Informasi Layanan</h2>'
),
modified = UTC_TIMESTAMP(),
modified_by = 0
WHERE id = 11
  AND alias = 'jenis-layanan-pada-ptsp-pengadilan-negeri-natuna'
  AND introtext LIKE '%<h2>Layanan per Kepaniteraan</h2>%'
  AND introtext NOT LIKE '%Lihat layanan Kepaniteraan Khusus Perikanan%';
