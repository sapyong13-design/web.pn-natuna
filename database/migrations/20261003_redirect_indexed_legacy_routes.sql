-- Preserve Google signals from the legacy Joomla /index.php/en routes after the
-- rebuilt site becomes pn-natuna.go.id. Targets are live routes verified against
-- the rebuilt menu. Joomla Redirect handles these after the old path returns 404.

UPDATE #__extensions SET enabled=1
WHERE type='plugin' AND folder='system' AND element='redirect';

INSERT INTO #__redirect_links (old_url,new_url,referer,comment,hits,published,created_date,modified_date)
SELECT source,target,'','Legacy indexed route mapped during main-domain cutover',0,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()
FROM (
  SELECT '/index.php/en/berita/artikel' source, '/berita-dan-pengumuman/berita' target
  UNION ALL SELECT '/index.php/en/hubungi-kami', '/kontak'
  UNION ALL SELECT '/index.php/en/layanan-publik/pengumuman', '/berita-dan-pengumuman/pengumuman'
  UNION ALL SELECT '/index.php/en/berita/berita-terkini', '/berita-dan-pengumuman/berita'
  UNION ALL SELECT '/index.php/en/berita/photo-gallery', '/berita-dan-pengumuman/berita'
  UNION ALL SELECT '/index.php/en/layanan-publik/laporan/sakip', '/transparansi/sakip'
  UNION ALL SELECT '/index.php/en/layanan-publik/laporan/lhkpn', '/transparansi/lhkpn'
  UNION ALL SELECT '/index.php/berita/berita-terkini/pelaksanaan-upcara-bendera-hut-ri-ke-80', '/berita/legacy-pelaksanaan-upcara-bendera-hut-ri-ke-80'
) mapped
WHERE NOT EXISTS (
  SELECT 1 FROM #__redirect_links existing WHERE existing.old_url=mapped.source
);
