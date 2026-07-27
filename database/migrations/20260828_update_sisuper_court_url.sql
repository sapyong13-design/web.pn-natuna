-- Point the SiSuper quick-service card to PN Natuna's dedicated Badilum survey page.
UPDATE #__modules
SET content = REPLACE(
    content,
    'href="https://esurvey.badilum.mahkamahagung.go.id/"',
    'href="https://esurvey.badilum.mahkamahagung.go.id/index.php/pengadilan/672948"'
)
WHERE id = 112
  AND module = 'mod_custom'
  AND position = 'quick-links'
  AND content LIKE '%<strong>SiSuper</strong>%'
  AND content LIKE '%href="https://esurvey.badilum.mahkamahagung.go.id/"%';
