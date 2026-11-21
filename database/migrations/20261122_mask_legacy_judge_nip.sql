-- Some production judge cards store NIP as flat text instead of dt/dd.
SET @legacy_nip_field := '(<div>[[:space:]]*NIP[.]?[[:space:]]*[: ]?[[:space:]]*[0-9]{2})[0-9]{14}([0-9]{2}[[:space:]]*</div>)';
SET @legacy_nip_ref := IF(VERSION() LIKE '%MariaDB%', CHAR(92 USING utf8mb4), '$');
SET @legacy_nip_mask := CONCAT(@legacy_nip_ref, '1', REPEAT('*', 14), @legacy_nip_ref, '2');

UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, @legacy_nip_field, @legacy_nip_mask),
    `fulltext` = REGEXP_REPLACE(`fulltext`, @legacy_nip_field, @legacy_nip_mask),
    modified = UTC_TIMESTAMP()
WHERE id = 58 AND alias = 'profil-hakim'
  AND (introtext REGEXP @legacy_nip_field OR `fulltext` REGEXP @legacy_nip_field);
