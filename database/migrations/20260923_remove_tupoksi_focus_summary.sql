-- Remove the redundant hero focus summary; its points already appear in the page copy and detail panels.
SET @summary_start := '<div class="tupoksi-summary-card" aria-label="Ringkasan tugas pokok">';
SET @summary_block := '<div class="tupoksi-summary-card" aria-label="Ringkasan tugas pokok">\n        <strong>Fokus layanan</strong>\n        <span>Perkara pidana &amp; perdata</span>\n        <span>Administrasi peradilan</span>\n        <span>Pelayanan hukum masyarakat</span>\n      </div>';

UPDATE #__content
SET introtext = REPLACE(introtext, @summary_block, ''),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'tugas-pokok-fungsi'
  AND introtext LIKE CONCAT('%', @summary_start, '%');
