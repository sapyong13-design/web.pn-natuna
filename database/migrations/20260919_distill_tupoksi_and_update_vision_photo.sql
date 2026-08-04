-- Distill the Tugas Pokok dan Fungsi page and replace the Visi Misi illustration with the courthouse photo.
SET @tupoksi_alias := 'tugas-pokok-fungsi';
SET @process_marker := '<section class="tupoksi-process"';

UPDATE #__content
SET introtext = CONCAT(
        SUBSTRING_INDEX(introtext, @process_marker, 1),
        SUBSTRING(
            introtext,
            LOCATE('</section>', LOCATE(@process_marker, introtext)) + CHAR_LENGTH('</section>')
        )
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = @tupoksi_alias
  AND introtext LIKE CONCAT('%', @process_marker, '%');

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<h2>Tugas Pokok dan Fungsi Pengadilan Negeri Natuna Kelas II</h2>',
        '<h2>Mengadili Perkara Pidana dan Perdata pada Tingkat Pertama</h2>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = @tupoksi_alias
  AND introtext LIKE '%<h2>Tugas Pokok dan Fungsi Pengadilan Negeri Natuna Kelas II</h2>%';

SET @service_note := '<section class="tupoksi-service-note">';
SET @next_steps := '<nav class="tupoksi-next-steps" aria-label="Layanan terkait"><div><p class="tupoksi-panel-label">Layanan terkait</p><h3>Lanjutkan ke layanan yang Anda perlukan</h3></div><ul><li><a href="/layanan-publik/jenis-layanan-ptsp"><strong>Jenis Layanan PTSP</strong><span>Lihat layanan yang tersedia melalui Pelayanan Terpadu Satu Pintu.</span></a></li><li><a href="/informasi-perkara/prosedur-pengajuan-perkara"><strong>Prosedur Pengajuan Perkara</strong><span>Pelajari tahapan dan persyaratan pengajuan perkara.</span></a></li><li><a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener"><strong>Informasi Perkara / SIPP</strong><span>Telusuri status dan informasi perkara melalui sistem resmi.</span></a></li></ul></nav>';

UPDATE #__content
SET introtext = REPLACE(introtext, @service_note, CONCAT(@next_steps, @service_note)),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = @tupoksi_alias
  AND introtext LIKE CONCAT('%', @service_note, '%')
  AND introtext NOT LIKE '%class="tupoksi-next-steps"%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<img src="/images/ilustrasi/visi-misi-pn-natuna.svg" alt="Ilustrasi visi misi Pengadilan Negeri Natuna" width="960" height="720" loading="eager" decoding="async">',
        '<img src="/images/layanan/gallery/lokasi-kantor-2026.webp" alt="Gedung Pengadilan Negeri Natuna Kelas II" width="1200" height="800" loading="eager" decoding="async">'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'visi-dan-misi-pengadilan-negeri-natuna'
  AND introtext LIKE '%/images/ilustrasi/visi-misi-pn-natuna.svg%';

UPDATE #__content
SET introtext = REPLACE(
        introtext,
        '<figcaption>Ilustrasi arah pelayanan dan cita-cita Pengadilan Negeri Natuna.</figcaption>',
        '<figcaption>Gedung Pengadilan Negeri Natuna Kelas II sebagai pusat pelayanan peradilan bagi masyarakat Kabupaten Natuna.</figcaption>'
    ),
    modified = UTC_TIMESTAMP(),
    modified_by = 0
WHERE alias = 'visi-dan-misi-pengadilan-negeri-natuna'
  AND introtext LIKE '%<figcaption>Ilustrasi arah pelayanan dan cita-cita Pengadilan Negeri Natuna.</figcaption>%';
