-- Shared hosting spends several seconds rebuilding identical guest pages. Joomla's
-- native page cache is server-side only; browsercache remains off so HTML freshness
-- and authenticated sessions retain their existing behavior.
UPDATE #__extensions
SET enabled = 1,
    params = '{"browsercache":"0","cachetime":"15"}'
WHERE type = 'plugin'
  AND folder = 'system'
  AND element = 'cache';

-- Images outside the first viewport must not keep the browser load indicator alive.
-- Full module assignments keep this migration replay-safe and preserve their content.
UPDATE #__modules
SET content = '<div class="footer-signature"><div class="footer-brand"><img class="footer-logo" src="/images/brand/logo-pn-natuna.webp" alt="Logo Pengadilan Negeri Natuna" width="44" height="44" loading="lazy" decoding="async"><div class="footer-brand-text"><strong>Pengadilan Negeri Natuna Kelas II</strong><p>Informasi layanan dan kontak resmi pengadilan.</p><address>Jalan Batu Sisir, Desa Sungai Ulu, Kecamatan Bunguran Timur, Kabupaten Natuna, Provinsi Kepulauan Riau.</address></div></div></div>'
WHERE id = 117 AND module = 'mod_custom';

UPDATE #__modules
SET content = '<h2>Role Model PN Natuna</h2><div class="role-carousel role-model-instagram" aria-label="Role model PN Natuna"><div class="role-carousel-viewport"><article class="role-slide is-active"><img src="/images/role-model/joko-ciptanto-role-model-2026.webp" alt="Joko Ciptanto, S.H., M.H - Wakil Ketua Pengadilan Negeri Natuna" loading="lazy" decoding="async"></article><article class="role-slide"><img src="/images/role-model/marihod-tua-lubis-role-model-2026.jpg" alt="Marihod Tua Lubis, S.H - Agen Perubahan Pengadilan Negeri Natuna" loading="lazy" decoding="async"></article></div><div class="role-carousel-dots" aria-label="Pilih role model"><button type="button" data-role-slide="0" aria-label="Tampilkan Joko Ciptanto"></button><button type="button" data-role-slide="1" aria-label="Tampilkan Marihod Tua Lubis"></button></div></div>'
WHERE id = 482 AND module = 'mod_custom';

UPDATE #__modules
SET content = '<div class="court-badges court-brand-badges header-brand-lockup"><a class="ampuh-certified-link" href="/ampuh" aria-label="Sertifikasi Mutu Pengadilan Unggul dan Tangguh"><img class="ampuh-certified-mark" src="/images/brand/logo-ampuh-certified.webp" alt="Sertifikasi Mutu Pengadilan Unggul dan Tangguh" width="450" height="450" loading="lazy" decoding="async"></a><a class="asn-berakhlak-link" href="/sk-ampuh" aria-label="ASN BerAKHLAK Bangga Melayani Bangsa"><img class="asn-berakhlak-mark asn-berakhlak-mark--light" src="/images/brand/logo-asn-berakhlak.webp" alt="BerAKHLAK Bangga Melayani Bangsa" width="1024" height="262" loading="lazy" decoding="async"><img class="asn-berakhlak-mark asn-berakhlak-mark--dark" src="/images/brand/logo-asn-berakhlak-dark.webp" alt="" width="1024" height="262" aria-hidden="true" loading="lazy" decoding="async"></a></div>'
WHERE id = 806 AND module = 'mod_custom';

UPDATE #__modules
SET content = '<div class="footer-social-card"><span>Kanal Resmi</span><a class="social-link" href="https://www.instagram.com/pn.natuna/" target="_blank" rel="noopener"><img src="/images/social/instagram.svg" alt="Instagram" loading="lazy" decoding="async"><span>Instagram</span></a><a class="social-link" href="https://www.facebook.com/pn.natuna" target="_blank" rel="noopener"><img src="/images/social/facebook.svg" alt="Facebook" loading="lazy" decoding="async"><span>Facebook</span></a><a class="social-link" href="https://www.youtube.com/@pengadilannegeriranai9849" target="_blank" rel="noopener"><img src="/images/social/youtube.svg" alt="YouTube" loading="lazy" decoding="async"><span>YouTube</span></a></div>'
WHERE id = 813 AND module = 'mod_custom';
