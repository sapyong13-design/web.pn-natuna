-- Sensor NIP di metadata profil publik tanpa mengubah nama, jabatan, atau golongan.
-- Tampilkan dua digit awal dan dua digit akhir saja. Nilai tersensor tidak cocok lagi.
SET @pn_natuna_nip_field := '(<dt>[[:space:]]*NIP[.]?[[:space:]]*</dt>[[:space:]]*<dd>[[:space:]]*[0-9]{2})[0-9]{14}([0-9]{2}[[:space:]]*</dd>)';
-- MySQL dan MariaDB memakai awalan backreference yang berbeda.
SET @pn_natuna_nip_ref := IF(VERSION() LIKE '%MariaDB%', CHAR(92 USING utf8mb4), '$');
SET @pn_natuna_nip_mask := CONCAT(@pn_natuna_nip_ref, '1', REPEAT('*', 14), @pn_natuna_nip_ref, '2');

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @pn_natuna_nip_field, @pn_natuna_nip_mask),
    `fulltext` = REGEXP_REPLACE(`fulltext`, @pn_natuna_nip_field, @pn_natuna_nip_mask),
    modified = UTC_TIMESTAMP()
WHERE introtext REGEXP @pn_natuna_nip_field
   OR `fulltext` REGEXP @pn_natuna_nip_field;
