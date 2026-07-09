-- Update Maklumat Pelayanan Article (id 13) to use the new PNG image signed by Joko Ciptanto
UPDATE pnn_content 
SET introtext = REPLACE(introtext, 'maklumat-pelayanan-2026.jpg', 'maklumat-pelayanan-2026.png') 
WHERE id = 13;

-- Update the signature in Article 13 to match the current pimpinan (Joko Ciptanto as Wakil Ketua)
UPDATE pnn_content 
SET introtext = REPLACE(introtext, 'Lodewyk Ivandrie Simanjuntak, S.H., M.H. &#8212; Ketua Pengadilan Negeri Natuna', 'Joko Ciptanto, S.H., M.H. &#8212; Wakil Ketua Pengadilan Negeri Natuna') 
WHERE id = 13;
