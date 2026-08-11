-- Serve card-sized facility photos instead of the 1200-1600px documentary sources.
-- Each exact replacement is replay-safe because the original tag is absent afterward.
UPDATE #__modules
SET content = REPLACE(
  content,
  'src="/images/layanan/gallery/ruang-ptsp-2026.webp" alt="Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna" loading="lazy"',
  'src="/images/layanan/gallery/ruang-ptsp-2026.webp" srcset="/images/layanan/gallery/ruang-ptsp-2026-400.webp 400w, /images/layanan/gallery/ruang-ptsp-2026-800.webp 800w, /images/layanan/gallery/ruang-ptsp-2026-1200.webp 1200w, /images/layanan/gallery/ruang-ptsp-2026.webp 1600w" sizes="(max-width: 760px) calc(100vw - 40px), 280px" decoding="async" alt="Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna" loading="lazy"'
)
WHERE id = 480
  AND module = 'mod_custom'
  AND title = 'Galeri Fasilitas Publik'
  AND content LIKE '%src="/images/layanan/gallery/ruang-ptsp-2026.webp" alt="Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna" loading="lazy"%';

UPDATE #__modules
SET content = REPLACE(
  content,
  'src="/images/layanan/gallery/akses-disabilitas-2026.webp" alt="Tiga kursi roda untuk layanan prioritas penyandang disabilitas" loading="lazy"',
  'src="/images/layanan/gallery/akses-disabilitas-2026.webp" srcset="/images/layanan/gallery/akses-disabilitas-2026-400.webp 400w, /images/layanan/gallery/akses-disabilitas-2026-800.webp 800w, /images/layanan/gallery/akses-disabilitas-2026.webp 1200w" sizes="(max-width: 760px) calc(100vw - 40px), 280px" decoding="async" alt="Tiga kursi roda untuk layanan prioritas penyandang disabilitas" loading="lazy"'
)
WHERE id = 480
  AND module = 'mod_custom'
  AND title = 'Galeri Fasilitas Publik'
  AND content LIKE '%src="/images/layanan/gallery/akses-disabilitas-2026.webp" alt="Tiga kursi roda untuk layanan prioritas penyandang disabilitas" loading="lazy"%';

UPDATE #__modules
SET content = REPLACE(
  content,
  'src="/images/layanan/gallery/posbakum-2026.webp" alt="Meja layanan Pos Bantuan Hukum dan fasilitas kursi roda Pengadilan Negeri Natuna" loading="lazy"',
  'src="/images/layanan/gallery/posbakum-2026.webp" srcset="/images/layanan/gallery/posbakum-2026-400.webp 400w, /images/layanan/gallery/posbakum-2026-800.webp 800w, /images/layanan/gallery/posbakum-2026.webp 1200w" sizes="(max-width: 760px) calc(100vw - 40px), 280px" decoding="async" alt="Meja layanan Pos Bantuan Hukum dan fasilitas kursi roda Pengadilan Negeri Natuna" loading="lazy"'
)
WHERE id = 480
  AND module = 'mod_custom'
  AND title = 'Galeri Fasilitas Publik'
  AND content LIKE '%src="/images/layanan/gallery/posbakum-2026.webp" alt="Meja layanan Pos Bantuan Hukum dan fasilitas kursi roda Pengadilan Negeri Natuna" loading="lazy"%';

UPDATE #__modules
SET content = REPLACE(
  content,
  'src="/images/layanan/gallery/lokasi-kantor-2026.webp" alt="Fasad gedung Pengadilan Negeri Natuna Kelas II" loading="lazy"',
  'src="/images/layanan/gallery/lokasi-kantor-2026.webp" srcset="/images/layanan/gallery/lokasi-kantor-2026-400.webp 400w, /images/layanan/gallery/lokasi-kantor-2026-800.webp 800w, /images/layanan/gallery/lokasi-kantor-2026.webp 1200w" sizes="(max-width: 760px) calc(100vw - 40px), 280px" decoding="async" alt="Fasad gedung Pengadilan Negeri Natuna Kelas II" loading="lazy"'
)
WHERE id = 480
  AND module = 'mod_custom'
  AND title = 'Galeri Fasilitas Publik'
  AND content LIKE '%src="/images/layanan/gallery/lokasi-kantor-2026.webp" alt="Fasad gedung Pengadilan Negeri Natuna Kelas II" loading="lazy"%';
