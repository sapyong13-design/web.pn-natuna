<?php
defined('_JEXEC') or die;

function pn_natuna_instansi_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function pn_natuna_instansi_url(string $href, string $base): string
{
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }

    $parts = parse_url($base);
    $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

    if (str_starts_with($href, '/')) {
        return $origin . $href;
    }

    $path = $parts['path'] ?? '/';
    $dir = rtrim(substr($path, 0, strrpos($path, '/') ?: 0), '/');
    return $origin . $dir . '/' . $href;
}

function pn_natuna_instansi_fetch_url(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36\r\nAccept: text/html,application/rss+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $html = @file_get_contents($url, false, $context);
    return is_string($html) ? $html : '';
}

function pn_natuna_instansi_fetch_google_news(string $query): array
{
    $url = 'https://news.google.com/rss/search?q=' . rawurlencode($query) . '&hl=id&gl=ID&ceid=ID:id';
    $xml = pn_natuna_instansi_fetch_url($url);
    if ($xml === '' || !str_contains($xml, '<item>')) {
        return [];
    }
    libxml_use_internal_errors(true);
    $simple = @simplexml_load_string($xml);
    if ($simple === false || !isset($simple->channel->item)) {
        return [];
    }
    $items = [];
    foreach ($simple->channel->item as $entry) {
        $title = pn_natuna_instansi_text((string) $entry->title);
        $title = preg_replace('/\s*[-–]\s*Mahkamah Agung$/i', '', $title);
        $link = trim((string) $entry->link);
        $pub = (string) $entry->pubDate;
        $items[] = [
            'title' => $title,
            'url' => $link !== '' ? $link : 'https://www.mahkamahagung.go.id/',
            'date' => pn_natuna_instansi_item_date($pub),
            'pub' => strtotime($pub) ?: 0,
        ];
    }
    return $items;
}

function pn_natuna_instansi_item_date(string $text): string
{
    $months = 'Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des|Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember';
    if (preg_match('/\b(\d{1,2})-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*-(\d{4})\b/iu', $text, $match)) {
        $map = ['may' => 'Mei', 'aug' => 'Agu', 'oct' => 'Okt', 'dec' => 'Des'];
        $month = mb_substr($match[2], 0, 3);
        $month = $map[mb_strtolower($month)] ?? $month;
        return sprintf('%02d %s', (int) $match[1], $month);
    }
    if (preg_match('/\b(\d{1,2})\s+(' . $months . ')\w*\s+(\d{4})\b/iu', $text, $match)) {
        return sprintf('%02d %s', (int) $match[1], mb_substr($match[2], 0, 3));
    }
    if (preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})\b/u', $text, $match)) {
        return sprintf('%02d/%02d', (int) $match[1], (int) $match[2]);
    }
    if (preg_match('/\b(20\d{2})[-\/](\d{1,2})[-\/](\d{1,2})\b/u', $text, $match)) {
        return sprintf('%02d/%02d', (int) $match[3], (int) $match[2]);
    }
    return 'Baru';
}

function pn_natuna_instansi_parse_items(string $html, string $baseUrl, array $include, array $exclude = []): array
{
    $items = [];

    if ($html === '') {
        return $items;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//a[@href]') as $anchor) {
        $title = pn_natuna_instansi_text($anchor->textContent ?? '');
        $href = trim((string) $anchor->getAttribute('href'));

        if ($title === "" || mb_strlen($title) < 18 || $href === "#" || $href === "http://-" || $href === "https://-") {
            continue;
        }

        $node = $anchor;
        $inTable = false;
        while ($node) {
            $tag = mb_strtolower($node->nodeName ?? "");
            if ($tag === "td" || $tag === "table") {
                $inTable = true;
            }
            $markers = mb_strtolower((string) ($node->attributes?->getNamedItem("class")?->nodeValue ?? "") . " " . (string) ($node->attributes?->getNamedItem("id")?->nodeValue ?? ""));
            if (in_array($tag, ["nav", "header", "aside", "footer"], true) || preg_match("/menu|dropdown|nav|header|breadcrumb|footer|sidebar|aside|widget|banner|nav-child|column/i", $markers)) {
                continue 2;
            }
            $node = $node->parentNode;
        }
        if (str_contains($baseUrl, "badilum.mahkamahagung.go.id") && !$inTable) {
            continue;
        }

        $haystack = mb_strtolower($title . ' ' . $href);
        $matchesInclude = $include === [];
        foreach ($include as $word) {
            if (str_contains($haystack, mb_strtolower($word))) {
                $matchesInclude = true;
                break;
            }
        }
        foreach ($exclude as $word) {
            if (str_contains($haystack, mb_strtolower($word))) {
                continue 2;
            }
        }
        if (!$matchesInclude) {
            continue;
        }

        $context = $title;
        $node = $anchor->parentNode;
        for ($i = 0; $i < 3 && $node; $i++, $node = $node->parentNode) {
            $context .= ' ' . pn_natuna_instansi_text($node->textContent ?? '');
        }

        $url = pn_natuna_instansi_url($href, $baseUrl);
        $key = mb_strtolower($url);
        $items[$key] = [
            'title' => $title,
            'url' => $url,
            'date' => pn_natuna_instansi_item_date($context),
        ];

        if (count($items) >= 8) {
            break;
        }
    }

    return array_slice(array_values($items), 0, 5);
}

function pn_natuna_instansi_fallback(): array
{
    return [
        'ma' => [
            'title' => 'Mahkamah Agung RI',
            'class' => 'instansi-ma',
            'logo' => '/images/brand/logo-ma.png',
            'news' => [
                ['date' => '1 Jul', 'title' => 'Ketua MA Hadiri Upacara Peringatan HUT Ke-80 Bhayangkara', 'url' => 'https://mahkamahagung.go.id/id/berita/7330/ketua-ma-hadiri-upacara-peringatan-hut-ke-80-bhayangkara'],
                ['date' => '26 Jun', 'title' => 'Sekretaris MA Harap 25 Pejabat Pengawas yang Dilantik Menjadi Motor Penggerak Perubahan', 'url' => 'https://mahkamahagung.go.id/id/berita/7323/sekretaris-ma-harap-25-pejabat-pengawas-yang-dilantik-menjadi-motor-penggerak-perubahan'],
                ['date' => '25 Jun', 'title' => 'MA Goes to Campus 2026 Hadir di Yogyakarta, Perkuat Literasi Peradilan bagi Mahasiswa', 'url' => 'https://mahkamahagung.go.id/id/berita/7322/ma-goes-to-campus-2026-hadir-di-yogyakarta-perkuat-literasi-peradilan-bagi-mahasiswa'],
                ['date' => '25 Jun', 'title' => 'Satu Semester KUHP Nasional, Ketua MA Jelaskan Perubahan Sudut Pandang Pemidanaan', 'url' => 'https://mahkamahagung.go.id/id/berita/7321/satu-semester-kuhp-nasional-ketua-ma-jelaskan-perubahan-sudut-pandang-pemidanaan'],
                ['date' => '23 Jun', 'title' => 'Mahkamah Agung Terima Audiensi Serikat Buruh Jawa Timur, Bahas Persoalan Upah Proses Hingga Perlindungan Buruh', 'url' => 'https://mahkamahagung.go.id/id/berita/7320/mahkamah-agung-terima-audiensi-serikat-buruh-jawa-timur-bahas-persoalan-upah-proses-hingga-perlindungan-buruh'],
            ],
            'announcements' => [
                ['date' => '3 Jul', 'title' => 'Rekrutmen Calon Hakim Pengadilan Pajak Tahun Anggaran 2026', 'url' => 'https://mahkamahagung.go.id/id/pengumuman/7331/rekrutmen-calon-hakim-pengadilan-pajak-tahun-anggaran-2026'],
                ['date' => '30 Jun', 'title' => 'Pengisian Kelengkapan Berkas Guna Pengangkatan dalam Jabatan Fungsional PNS', 'url' => 'https://mahkamahagung.go.id/id/pengumuman/7328/pengisian-kelengkapan-berkas-guna-pengangkatan-dalam-jabatan-fungsional-pns'],
                ['date' => '30 Jun', 'title' => 'Pelaksanaan, Penyusunan dan Penyampaian Laporan Pengawasan dan Pengendalian Barang Milik Negara Semester I Tahun 2026', 'url' => 'https://mahkamahagung.go.id/id/pengumuman/7326/pelaksanaan-penyusunan-dan-penyampaian-laporan-pengawasan-dan-pengendalian-barang-milik-negara-semester-i-tahun-2026'],
                ['date' => '29 Jun', 'title' => 'Daftar Hasil RTPM Tenaga Kesekretariatan di Lingkungan Mahkamah Agung RI dan Badan Peradilan di Bawahnya', 'url' => 'https://mahkamahagung.go.id/id/pengumuman/7324/daftar-hasil-rtpm-tenaga-kesekretariatan-di-lingkungan-mahkamah-agung-ri-dan-badan-peradilan-di-bawahnya'],
                ['date' => '19 Jun', 'title' => 'Revisi Kebijakan Pelaksanaan Pembangunan dan Evaluasi ZI Menuju WBK/WBBM Tahun 2026', 'url' => 'https://mahkamahagung.go.id/id/pengumuman/7318/revisi-kebijakan-pelaksnaan-pembangunan-dan-evaluasi-zi-menuju-wbk-wbbm-tahun-2026'],
            ],
        ],
        'badilum' => [
            'title' => 'Direktorat Jenderal Badilum',
            'class' => 'instansi-badilum',
            'logo' => '/images/brand/logo-ma.png',
            'news' => [
                ['date' => '05 Jun', 'title' => 'Berita kegiatan Direktorat Jenderal Badilum', 'url' => 'https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html'],
                ['date' => '05 Jun', 'title' => 'Asesmen AMPUH satuan kerja peradilan umum', 'url' => 'https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html'],
                ['date' => '05 Jun', 'title' => 'Penilaian sertifikasi mutu AMPUH', 'url' => 'https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html'],
                ['date' => '05 Jun', 'title' => 'Kegiatan pembinaan dan monitoring Badilum', 'url' => 'https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html'],
                ['date' => '28 Mei', 'title' => 'Monev keadilan restoratif wilayah Jawa Timur', 'url' => 'https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html'],
            ],
            'announcements' => [
                ['date' => '26 Jun', 'title' => 'Undangan Perisai Badilum episode ke-17 daring', 'url' => 'https://badilum.mahkamahagung.go.id/pengumuman.html'],
                ['date' => '04 Jun', 'title' => 'Pemanggilan peserta Bimtek PBH Palembang', 'url' => 'https://badilum.mahkamahagung.go.id/pengumuman.html'],
                ['date' => 'Apr', 'title' => 'Hasil akhir calon Hakim Ad Hoc PHI 2026', 'url' => 'https://badilum.mahkamahagung.go.id/pengumuman.html'],
                ['date' => 'Apr', 'title' => 'Profile assessment calon pimpinan PN kelas II', 'url' => 'https://badilum.mahkamahagung.go.id/pengumuman.html'],
                ['date' => 'Mar', 'title' => 'Pengumuman penilaian kinerja satuan kerja 2026', 'url' => 'https://badilum.mahkamahagung.go.id/pengumuman.html'],
            ],
        ],
        'pt' => [
            'title' => 'Pengadilan Tinggi Kepulauan Riau',
            'class' => 'instansi-pt',
            'logo' => '/images/brand/logo-pt-kepri.png',
            'news' => [
                ['date' => '09 Jun', 'title' => 'Rapat evaluasi AKIP internal PT Kepulauan Riau', 'url' => 'https://pt-kepri.go.id/'],
                ['date' => '25 Mei', 'title' => 'Panitera PT Kepri hadiri kegiatan aksi perubahan', 'url' => 'https://pt-kepri.go.id/'],
                ['date' => '16 Mei', 'title' => 'Ketua PT Kepri hadiri peresmian Koperasi Merah Putih', 'url' => 'https://pt-kepri.go.id/'],
                ['date' => '02 Feb', 'title' => 'Pelantikan Panitera Muda Tipikor PT Kepri', 'url' => 'https://pt-kepri.go.id/'],
                ['date' => 'Jan', 'title' => 'Pakta integritas dan komitmen bersama tahun 2026', 'url' => 'https://pt-kepri.go.id/'],
            ],
            'announcements' => [
                ['date' => '19 Jun', 'title' => 'Pengumuman pemenang POSBAKUM PT Kepri', 'url' => 'https://pt-kepri.go.id/pengumuman'],
                ['date' => '2026', 'title' => 'Informasi layanan publik PT Kepri', 'url' => 'https://pt-kepri.go.id/layanan-publik'],
                ['date' => '2026', 'title' => 'Standar pelayanan pengadilan tinggi', 'url' => 'https://pt-kepri.go.id/standar-pelayanan'],
                ['date' => '2026', 'title' => 'Informasi PTSP dan administrasi peradilan', 'url' => 'https://pt-kepri.go.id/ptsp'],
                ['date' => '2026', 'title' => 'Pengumuman dan kabar wilayah hukum PT Kepri', 'url' => 'https://pt-kepri.go.id/berita'],
            ],
        ],
    ];
}

function pn_natuna_instansi_load(): array
{
    $cacheFile = JPATH_ROOT . '/cache/pn_natuna_instansi_feed.json';
    $ttl = 3600;

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $data = pn_natuna_instansi_fallback();
    $sources = [
        'badilum' => [
            'news' => ['https://badilum.mahkamahagung.go.id/berita/berita-kegiatan.html', ['berita'], ['pengumuman']],
            'announcements' => ['https://badilum.mahkamahagung.go.id/berita/pengumuman-surat-dinas.html', ['pengumuman', 'undangan', 'pemanggilan', 'hasil', 'peserta', 'imbauan', 'pemberitahuan', 'informasi', 'penyampaian', 'pemantauan'], ['berita-kegiatan', 'mutasi hakim', 'mutasi panitera', 'peraturan perundangan', 'hasil survei', 'biaya mutasi', 'keuangan perkara']],
        ],
        'pt' => [
            'news' => ['https://pt-kepri.go.id/', ['/kepri/'], ['pengumuman', 'pengantar', 'visi', 'struktur', 'wilayah', 'yurisdiksi', 'ketua', 'wakil', 'sejarah', 'tugas', 'fungsi', 'kepaniteraan', 'pegawai', 'role-model']],
            'announcements' => ['https://pt-kepri.go.id/pengumuman', ['pengumuman'], ['berita']],
        ],
    ];

    foreach ($sources as $key => $groups) {
        foreach ($groups as $group => [$url, $include, $exclude]) {
            $items = pn_natuna_instansi_parse_items(pn_natuna_instansi_fetch_url($url), $url, $include, $exclude);
            if (count($items) >= 2) {
                $fallback = $data[$key][$group] ?? [];
                $seen = array_fill_keys(array_map(static fn ($item) => mb_strtolower($item["title"]), $items), true);
                foreach ($fallback as $item) {
                    if (count($items) >= 5) {
                        break;
                    }
                    if (!isset($seen[mb_strtolower($item["title"])])) {
                        $items[] = $item;
                    }
                }
                $data[$key][$group] = array_slice($items, 0, 5);
            }
        }
    }

    @mkdir(dirname($cacheFile), 0775, true);
    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $data;
}

function pn_natuna_instansi_render_list(array $items): void
{
    echo '<ul class="instansi-compact-list">';
    foreach (array_slice($items, 0, 5) as $item) {
        echo '<li><a href="' . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">';
        echo '<time class="list-date">' . htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8') . '</time>';
        echo '<span>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</span>';
        echo '</a></li>';
    }
    echo '</ul>';
}

function pn_natuna_render_instansi_feed(): void
{
    $data = pn_natuna_instansi_load();
    ?>
    <div class="module-card instansi-news-board instansi-news-compact">

      <div class="instansi-news-grid">
        <?php foreach ($data as $instansi) : ?>
          <section class="instansi-news-column <?php echo htmlspecialchars($instansi['class'], ENT_QUOTES, 'UTF-8'); ?>">
            <h3 class="instansi-category-title">
              <?php if (!empty($instansi['logo'])) : ?>
                <img src="<?php echo htmlspecialchars($instansi['logo'], ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
              <?php endif; ?>
              <span><?php echo htmlspecialchars($instansi['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            </h3>
            <div class="instansi-sub-grid">
              <div class="instansi-sub-col">
                <h3>Berita</h3>
                <?php pn_natuna_instansi_render_list($instansi['news']); ?>
              </div>
              <div class="instansi-sub-col">
                <h3>Pengumuman</h3>
                <?php pn_natuna_instansi_render_list($instansi['announcements']); ?>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
