-- Canonical lazy-load conversion for the homepage Google Maps iframe.
UPDATE #__modules
SET content=REPLACE(content,
'src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai&t=&z=17&ie=UTF8&iwloc=&output=embed"',
'data-src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai&t=&z=17&ie=UTF8&iwloc=&output=embed"')
WHERE content LIKE '%home-map-card%' AND content LIKE '%src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai%';
