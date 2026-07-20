-- Defer Google Maps iframe loading until the location card approaches the viewport.
UPDATE #__modules
SET content=REPLACE(content,
'src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai&amp;t=&amp;z=17&amp;ie=UTF8&amp;iwloc=&amp;output=embed"',
'data-src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai&amp;t=&amp;z=17&amp;ie=UTF8&amp;iwloc=&amp;output=embed"')
WHERE content LIKE '%home-map-card%' AND content LIKE '%src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai%';
