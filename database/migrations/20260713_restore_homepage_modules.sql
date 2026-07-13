-- Canonical homepage module state after importing any older Joomla dump.
-- #__ is replaced with the configured Joomla table prefix by apply-db-migrations.py.

UPDATE #__modules
SET content = REPLACE(
    content,
    '/images/role-model/joko-ciptanto-role-model-2026.png',
    '/images/role-model/joko-ciptanto-role-model-2026.webp'
)
WHERE id = 482;

UPDATE #__modules
SET title = 'Maklumat Layanan',
    showtitle = 1,
    content = '<div class="maklumat-compact"><div class="maklumat-compact-docs"><article class="maklumat-compact-doc"><button type="button" class="maklumat-doc" data-maklumat-zoom="/images/layanan/maklumat-pelayanan-2026.webp" data-maklumat-label="Maklumat Pelayanan Pengadilan Negeri Natuna" aria-label="Perbesar Maklumat Pelayanan"><img src="/images/layanan/maklumat-pelayanan-2026.webp" alt="Maklumat Pelayanan Pengadilan Negeri Natuna" loading="lazy" decoding="async"><span class="maklumat-zoom-hint" aria-hidden="true">Perbesar</span></button><div><h3>Maklumat Pelayanan</h3><p>Komitmen pelayanan kepada masyarakat pencari keadilan.</p><a href="/layanan-publik/maklumat-pelayanan">Baca selengkapnya</a></div></article><article class="maklumat-compact-doc"><button type="button" class="maklumat-doc" data-maklumat-zoom="/images/layanan/maklumat-layanan-informasi-publik.png" data-maklumat-label="Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" aria-label="Perbesar Maklumat Layanan Informasi Publik"><img src="/images/layanan/maklumat-layanan-informasi-publik.png" alt="Maklumat Layanan Informasi Publik Pengadilan Negeri Natuna" loading="lazy" decoding="async"><span class="maklumat-zoom-hint" aria-hidden="true">Perbesar</span></button><div><h3>Maklumat Layanan Informasi Publik</h3><p>Komitmen PPID memberi informasi secara cepat, tepat waktu, ringan biaya, dan sederhana.</p><a href="/permohonan-informasi">Ajukan permohonan informasi</a></div></article></div></div>',
    published = 1
WHERE id = 808;

UPDATE #__modules
SET title = 'Kinerja & Akuntabilitas',
    content = '<h2>Kinerja &amp; Akuntabilitas</h2><div class="survey-scores"><button type="button" class="survey-score-tile" data-maklumat-zoom="/images/surveys/SKM_TW2_2026.png" data-maklumat-label="Publikasi Indeks Kepuasan Masyarakat (SKM) — TW2 2026"><span class="survey-score-label">SKM / IKM</span><span class="survey-score-value">3,97 <em>/ 4,00</em></span><span class="survey-score-bar"><i style="--score-pct:99.27%"></i></span><span class="survey-score-meta">99,27% &middot; 61 responden</span></button><button type="button" class="survey-score-tile" data-maklumat-zoom="/images/surveys/IPAK_TW2_2026.png" data-maklumat-label="Publikasi Indeks Persepsi Anti Korupsi (IPAK) — TW2 2026"><span class="survey-score-label">IPAK</span><span class="survey-score-value">4,00 <em>/ 4,00</em></span><span class="survey-score-bar"><i style="--score-pct:100%"></i></span><span class="survey-score-meta">100,00% &middot; 61 responden</span></button></div><p class="survey-scores-note">Indeks periode TW2 2026 (April&ndash;Juni). Klik kartu skor untuk dokumen publikasi.</p><div class="dipa-widget"><div class="dipa-subhead">Realisasi Anggaran DIPA</div><div class="dipa-period">Periode Juni 2026</div><a class="dipa-link" href="https://drive.google.com/file/d/10qg6k5u9gosY2w4pw322LJOFQSgS5QYg/view" target="_blank" rel="noopener" title="Buka laporan PDF"><div class="dipa-grid"><div class="dipa-item"><div class="dipa-ring" style="--pct:54.96;--dipa-color:#1f5b4b;"><span class="dipa-ring-pct">54,96%</span></div><span class="dipa-label">DIPA 01</span><span class="dipa-amount">Rp 14.34 miliar</span><span class="dipa-sub">terserap Rp 7.88 miliar</span></div><div class="dipa-item"><div class="dipa-ring" style="--pct:42.46;--dipa-color:#8f1f0b;"><span class="dipa-ring-pct">42,46%</span></div><span class="dipa-label">DIPA 03</span><span class="dipa-amount">Rp 178.35 juta</span><span class="dipa-sub">terserap Rp 75.73 juta</span></div></div><span class="dipa-link-hint">Klik untuk lihat laporan PDF</span></a></div>',
    published = 1
WHERE id = 816;

UPDATE #__modules
SET published = 0
WHERE id = 817;
