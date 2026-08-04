-- Reconstruct the malformed Tupoksi wrapper and hero, preserving the valid grid and following sections.
SET @grid_marker := '<section class="tupoksi-grid">';
SET @tupoksi_hero := '<div class="tupoksi-page">\n  <section class="tupoksi-hero-card">\n    <div>\n      <p class="tupoksi-kicker">Mandat peradilan umum tingkat pertama</p>\n      <h2>Mengadili Perkara Pidana dan Perdata pada Tingkat Pertama</h2>\n      <p>Pengadilan Negeri Natuna Kelas II melaksanakan kekuasaan kehakiman pada peradilan umum tingkat pertama. Inti layanannya adalah menerima, memeriksa, mengadili, dan menyelesaikan perkara pidana maupun perdata sesuai hukum acara dan ketentuan peraturan perundang-undangan.</p>\n    </div>\n    <div class="tupoksi-visual-stack">\n      <figure class="tupoksi-illustration">\n        <img src="/images/berita/2026-briefing-ptsp-1.jpeg" alt="Briefing pelayanan PTSP Pengadilan Negeri Natuna" width="1152" height="864" loading="eager" decoding="async">\n        <figcaption>Pelayanan dan koordinasi aparatur menjadi bagian dari pelaksanaan tugas pengadilan.</figcaption>\n      </figure>\n      <div class="tupoksi-summary-card" aria-label="Ringkasan tugas pokok">\n        <strong>Fokus layanan</strong>\n        <span>Perkara pidana &amp; perdata</span>\n        <span>Administrasi peradilan</span>\n        <span>Pelayanan hukum masyarakat</span>\n      </div>\n    </div>\n  </section>\n\n  ';

UPDATE #__content
SET introtext = CONCAT(
        @tupoksi_hero,
        SUBSTRING(introtext, LOCATE(@grid_marker, introtext))
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'tugas-pokok-fungsi'
  AND introtext LIKE CONCAT('%', @grid_marker, '%')
  AND (introtext NOT LIKE '<div class="tupoksi-page">%' OR introtext LIKE '%s="tupoksi-page">%');
