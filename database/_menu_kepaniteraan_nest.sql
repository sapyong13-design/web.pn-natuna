-- Pindahkan 4 menu unit kepaniteraan (213-216) jadi anak Profil Kepaniteraan (212).
-- Kelima node menempati lft 1016..1025 berurutan, sehingga nested set cukup diatur ulang lokal.
UPDATE pnn_menu SET lft = 1016, rgt = 1025 WHERE id = 212;
UPDATE pnn_menu SET parent_id = 212, level = 3, lft = 1017, rgt = 1018, path = 'profil-pengadilan/profil-kepaniteraan/kepaniteraan-pidana' WHERE id = 213;
UPDATE pnn_menu SET parent_id = 212, level = 3, lft = 1019, rgt = 1020, path = 'profil-pengadilan/profil-kepaniteraan/kepaniteraan-perdata' WHERE id = 214;
UPDATE pnn_menu SET parent_id = 212, level = 3, lft = 1021, rgt = 1022, path = 'profil-pengadilan/profil-kepaniteraan/kepaniteraan-hukum' WHERE id = 215;
UPDATE pnn_menu SET parent_id = 212, level = 3, lft = 1023, rgt = 1024, path = 'profil-pengadilan/profil-kepaniteraan/kepaniteraan-khusus-perikanan' WHERE id = 216;
