-- Google menolak endpoint maps.google.com/maps lama di dalam iframe dengan X-Frame-Options: SAMEORIGIN.
-- Gunakan endpoint embed kanonis; REGEXP_REPLACE menjaga migrasi idempoten untuk semua variasi atribut lama.
UPDATE #__modules
SET content = REGEXP_REPLACE(
    content,
    '(?:data-)*src="https://maps[.]google[.]com/maps[?]q=Kantor%20Pengadilan%20Negeri%20Ranai&t=&z=17&ie=UTF8&iwloc=&output=embed"',
    'data-src="https://www.google.com/maps?q=Pengadilan%20Negeri%20Natuna&z=17&output=embed"'
)
WHERE id = 810
  AND content LIKE '%maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai%';
