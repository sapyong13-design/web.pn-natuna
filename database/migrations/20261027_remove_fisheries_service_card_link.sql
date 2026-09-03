-- Keep the Kepaniteraan Khusus Perikanan PTSP card consistent with the other registry-service cards.
UPDATE #__content
SET
    introtext = REPLACE(introtext, '<a class="svc-link" href="/profil-pengadilan/profil-kepaniteraan/kepaniteraan-khusus-perikanan">Lihat layanan Kepaniteraan Khusus Perikanan <span aria-hidden="true">&rarr;</span></a>', ''),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE id = 11
  AND alias = 'jenis-layanan-pada-ptsp-pengadilan-negeri-natuna';
