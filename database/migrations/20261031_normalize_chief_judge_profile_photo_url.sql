-- Use a root-relative asset URL so the chief judge photo resolves on nested SEF routes.
UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="images/profil/pegawai/hakim/joko-ciptanto.jpg"',
        '<img src="/images/profil/pegawai/hakim/joko-ciptanto.jpg"'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'profil-hakim'
  AND introtext LIKE '%<img src="images/profil/pegawai/hakim/joko-ciptanto.jpg"%';
