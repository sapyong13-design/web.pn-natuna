<?php
/**
 * Poster Instagram portrait (1080x1350) berisi realisasi anggaran DIPA saja.
 *
 * Sumber angka: modul Joomla id 816 (`Kinerja & Akuntabilitas`) — panel `dipa-panel`
 * yang sama dengan yang tampil di beranda, jadi poster tidak pernah memuat angka
 * berbeda dari situs. Tidak ada angka yang ditulis manual di skrip ini.
 *
 * Pemakaian:
 *   php tools/make-dipa-poster.php                 # periode terbaru (poster+caption+animasi)
 *   php tools/make-dipa-poster.php --list          # daftar periode yang tersedia
 *   php tools/make-dipa-poster.php --period=2026-07
 *   php tools/make-dipa-poster.php --all           # semua periode + halaman galeri
 *   php tools/make-dipa-poster.php --out=poster.html --caption=caption.md --video=reel.html
 *
 * Tiga keluaran sekaligus, semua dari data yang sama:
 *   - poster HTML 1080x1350 (foto layar -> PNG)
 *   - caption/garis besar unggahan (Markdown)
 *   - animasi HTML yang bisa direkam jadi Reels (parameter `?t=` membuat frame
 *     deterministik, jadi video bisa dirender ulang kapan pun tanpa rekam tangan)
 *
 * Render PNG/MP4-nya lewat browser + ffmpeg; perintahnya di docs/social-poster.md.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/configuration.php';

$options = getopt('', ['out::', 'caption::', 'video::', 'period::', 'all', 'list',
    'python::', 'refresh-detail', 'no-detail', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "php tools/make-dipa-poster.php [--period=2026-09|'September 2026'] [--all] [--list]\n"
        . "                                  [--out=path] [--caption=path] [--video=path]\n"
        . "                                  [--python=path] [--refresh-detail] [--no-detail]\n");
    exit(0);
}

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db);
if ($db->connect_errno) {
    fwrite(STDERR, "Koneksi database gagal: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$table = $config->dbprefix . 'modules';
$result = $db->query("SELECT content FROM `{$table}` WHERE id = 816 LIMIT 1");
if (!$result || !$result->num_rows) {
    fwrite(STDERR, "Modul 816 tidak ditemukan.\n");
    exit(1);
}
$content = (string) $result->fetch_row()[0];

$document = new DOMDocument();
libxml_use_internal_errors(true);
$document->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $content . '</div>');
libxml_clear_errors();
$xpath = new DOMXPath($document);

// Pencocokan token kelas utuh: `contains(@class,"dipa-item")` juga menangkap wadah
// `dipa-items`, sehingga setiap kartu terhitung dua kali.
$find = static function (string $class, ?DOMNode $scope = null) use ($xpath): DOMNodeList {
    $predicate = '*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';
    return $scope === null ? $xpath->query('//' . $predicate) : $xpath->query('.//' . $predicate, $scope);
};
$text = static fn(?DOMNode $node): string => $node === null ? '' : trim(preg_replace('/\s+/u', ' ', $node->textContent));

/** "Rp 14.37 miliar" / "Rp 178.35 juta" -> rupiah. */
$rupiah = static function (string $value): float {
    if (!preg_match('~Rp\s*([\d.,]+)\s*(miliar|juta|ribu)?~i', $value, $match)) {
        return 0.0;
    }
    // Modul menulis "14.37" (titik desimal) dan sesekali "1.234,56" (gaya Indonesia).
    // Aturannya: bila ada koma, titik adalah pemisah ribuan; bila hanya titik dan polanya
    // persis ribuan (1–3 digit lalu kelompok 3 digit), itu juga pemisah ribuan.
    $raw = $match[1];
    if (str_contains($raw, ',')) {
        $number = (float) str_replace(',', '.', str_replace('.', '', $raw));
    } elseif (preg_match('~^\d{1,3}(\.\d{3})+$~', $raw)) {
        $number = (float) str_replace('.', '', $raw);
    } else {
        $number = (float) $raw;
    }
    $scale = match (strtolower($match[2] ?? '')) {
        'miliar' => 1e9,
        'juta' => 1e6,
        'ribu' => 1e3,
        default => 1.0,
    };
    return $number * $scale;
};
$percent = static fn(string $value): float => (float) str_replace(',', '.', rtrim(trim($value), '%'));
$points = static function (string $title): ?float {
    return preg_match('~Selisih\s*([+-]?[\d.,]+)\s*poin~i', $title, $match)
        ? (float) str_replace(',', '.', $match[1])
        : null;
};

$periods = [];
foreach ($find('dipa-panel') as $panel) {
    $items = [];
    foreach ($find('dipa-item', $panel) as $item) {
        $deltaNode = $find('dipa-delta', $item)->item(0);
        $items[] = [
            'label' => $text($find('dipa-label', $item)->item(0)),
            'percent' => $percent($text($find('dipa-ring-pct', $item)->item(0))),
            'percent_text' => $text($find('dipa-ring-pct', $item)->item(0)),
            'pagu' => $rupiah($text($find('dipa-amount', $item)->item(0))),
            'absorbed' => $rupiah($text($find('dipa-sub', $item)->item(0))),
            'delta' => $deltaNode === null ? null : $points($deltaNode->getAttribute('title')),
            'delta_text' => $text($deltaNode),
        ];
    }
    if (!$items) {
        continue;
    }
    $linkNode = $find('dipa-link', $panel)->item(0);
    $periods[] = [
        'label' => str_replace('Periode ', '', $text($find('dipa-period', $panel)->item(0))),
        'items' => $items,
        'link' => $linkNode instanceof DOMElement ? trim($linkNode->getAttribute('href')) : '',
    ];
}

if (count($periods) < 2) {
    fwrite(STDERR, "Panel DIPA tidak ditemukan di modul 816.\n");
    exit(1);
}

if (isset($options['list'])) {
    foreach ($periods as $period) {
        fwrite(STDOUT, $period['label'] . "\n");
    }
    exit(0);
}

/*
 * Periode terpilih. Modul menyimpan periode terbaru lebih dulu, jadi `--period`
 * memilih satu panel; grafiknya hanya memuat bulan sampai periode itu, bukan bulan
 * setelahnya — poster Agustus tidak boleh memperlihatkan angka September.
 * Penerimaan: "2026-09", "2026-9", "9", "Sep", atau "September 2026".
 */
if (isset($options['all'])) {
    $built = [];
    foreach ($periods as $period) {
        $slug = preg_replace('~[^0-9a-z]+~i', '-', $period['label']);
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__)
            . ' --period=' . escapeshellarg($period['label']);
        if (isset($options['python'])) {
            $command .= ' --python=' . escapeshellarg((string) $options['python']);
        }
        if (isset($options['refresh-detail'])) {
            $command .= ' --refresh-detail';
        }
        exec($command, $ignored, $code);
        if ($code !== 0) {
            fwrite(STDERR, "Gagal membuat poster {$period['label']}\n");
            exit(1);
        }
        $built[] = ['label' => $period['label'], 'slug' => $slug];
    }
    $gallery = $root . '/tmp/social/index.html';
    $cards = '';
    foreach ($built as $entry) {
        $slug = $entry['slug'];
        $files = [
            'png' => ['dipa-ig-' . $slug . '.png', 'PNG slide 1', 'Poster siap unggah'],
            'rincian' => ['dipa-ig-' . $slug . '-rincian.png', 'PNG slide 2', '10 akun terbesar'],
            'mp4' => ['dipa-ig-' . $slug . '.mp4', 'MP4 8 dtk', 'Reels 1080x1350'],
            'md' => ['dipa-ig-' . $slug . '.md', 'Caption', 'Garis besar unggahan'],
            'html' => ['dipa-ig-' . $slug . '.html', 'HTML', 'Poster sumber'],
        ];
        $buttons = '';
        foreach ($files as $kind => [$name, $label, $hint]) {
            if (!is_file(dirname($gallery) . '/' . $name)) {
                continue;
            }
            $download = ' download="' . $name . '"';
            $buttons .= '<a class="dl dl--' . $kind . '" href="' . $name . '"' . $download . ' title="'
                . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
        }
        if ($buttons === '') {
            $buttons = '<span class="dl dl--pending">belum dirender</span>';
        }
        $cards .= '<figure><div class="thumb"><iframe src="dipa-ig-' . $slug . '.html" loading="lazy" title="'
            . htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8') . '"></iframe></div>'
            . '<figcaption><strong>' . htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8') . '</strong>'
            . '<span class="files">' . $buttons . '</span></figcaption></figure>';
    }
    // Tiap poster aslinya 1080x1350. iframe selebar kartu akan memotong kanan-bawah
    // karena isinya tidak menyesuaikan diri, jadi poster dirender penuh di iframe
    // 1080px lalu diskalakan dengan transform dan dipotong oleh pembungkusnya.
    $scale = 0.3;
    $frameWidth = (int) round(1080 * $scale);
    $frameHeight = (int) round(1350 * $scale);
    $galleryHtml = '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<title>Galeri poster DIPA</title><style>'
        . 'body{margin:0;padding:36px;background:#f4efe6;font-family:system-ui,sans-serif;color:#2f140e}'
        . 'h1{font-size:22px;margin:0 0 6px}p.hint{margin:0 0 26px;color:#6b5a4c;font-size:14px}'
        . '.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(' . ($frameWidth + 12) . 'px,1fr));gap:26px}'
        . 'figure{margin:0}'
        . '.thumb{width:' . $frameWidth . 'px;height:' . $frameHeight . 'px;overflow:hidden;'
        . 'border:1px solid #d9cdb8;border-radius:10px;background:#fff;box-shadow:0 6px 18px rgba(47,20,14,.10)}'
        . '.thumb iframe{width:1080px;height:1350px;border:0;'
        . 'transform:scale(' . $scale . ');transform-origin:0 0}'
        . 'figcaption{margin-top:9px;font-size:12.5px;color:#5a4a3d}'
        . 'figcaption strong{display:block;margin-bottom:7px;font-size:13.5px}'
        . '.files{display:flex;flex-wrap:wrap;gap:6px}'
        . '.dl{display:inline-block;padding:4px 9px;border-radius:6px;font-size:11.5px;font-weight:700;'
        . 'text-decoration:none;border:1px solid rgba(143,31,11,.22);background:#fff;color:#8f1f0b}'
        . '.dl:hover{background:#8f1f0b;color:#fff;border-color:#8f1f0b}'
        . '.dl--mp4{border-color:rgba(185,143,36,.45);color:#7a5c10}'
        . '.dl--mp4:hover{background:#b98f24;border-color:#b98f24;color:#fff}'
        . '.dl--pending{border-color:#d9cdb8;background:#f2ece1;color:#9a8b7c;font-weight:600}'
        . '</style></head><body><h1>Galeri poster DIPA 2026</h1>'
        . '<p class="hint">Klik tombol di bawah tiap poster untuk mengunduh berkasnya. '
        . 'PNG untuk feed, MP4 untuk Reels, Caption untuk teks unggahan.</p>'
        . '<div class="grid">' . $cards . '</div></body></html>';
    file_put_contents($gallery, $galleryHtml);
    printf("%d poster dibuat. Galeri: %s\n", count($built), $gallery);
    exit(0);
}

$requested = isset($options['period']) ? trim((string) $options['period']) : '';
$selectedIndex = 0;
if ($requested !== '') {
    $monthsById = [
        1 => 'januari', 2 => 'februari', 3 => 'maret', 4 => 'april', 5 => 'mei', 6 => 'juni',
        7 => 'juli', 8 => 'agustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember',
    ];
    $needle = strtolower(str_replace('periode', '', $requested));
    $match = null;
    if (preg_match('~^(\d{4})\s*[-/\s]\s*(\d{1,2})$~', $needle, $m)) {
        $wanted = [$monthsById[(int) $m[2]] ?? '', $m[1]];
    } elseif (preg_match('~^([a-z]+)\s+(\d{4})$~', $needle, $m)) {
        $wanted = [$m[1], $m[2]];
    } elseif (preg_match('~^([a-z]{3,})$~', $needle, $m)) {
        $wanted = [$m[1], null];
    } elseif (preg_match('~^(\d{1,2})$~', $needle, $m)) {
        $wanted = [$monthsById[(int) $m[1]] ?? '', null];
    } else {
        $wanted = [$needle, null];
    }
    foreach ($periods as $index => $period) {
        $label = strtolower($period['label']);
        $okMonth = $wanted[0] !== '' && str_starts_with($label, $wanted[0]);
        $okYear = $wanted[1] === null || str_contains($label, $wanted[1]);
        if ($okMonth && $okYear) {
            $match = $index;
            break;
        }
    }
    if ($match === null) {
        fwrite(STDERR, "Periode '{$requested}' tidak ditemukan. Pilihan: "
            . implode(', ', array_map(static fn(array $p): string => $p['label'], $periods)) . "\n");
        exit(1);
    }
    $selectedIndex = $match;
}

// Urutan maju sampai periode terpilih, supaya grafik berhenti di bulan itu.
$latest = $periods[$selectedIndex];
$chronological = array_reverse(array_slice($periods, $selectedIndex));
// Periode paling awal tidak punya pembanding: kalimat "dibanding bulan sebelumnya"
// dan sorotan pertumbuhan sejak periode pertama tidak berlaku di sana.
$hasPrevious = isset($periods[$selectedIndex + 1]);
// Panel pembanding untuk kolom "sebelumnya -> sekarang" di kartu; null pada periode awal.
$previous = $hasPrevious ? $periods[$selectedIndex + 1] : null;

$series = [];
foreach ($chronological as $period) {
    foreach ($period['items'] as $item) {
        $series[$item['label']][] = [
            'label' => $period['label'],
            'percent' => $item['percent'],
        ];
    }
}

$pagu = $absorbed = 0.0;
foreach ($latest['items'] as $item) {
    $pagu += $item['pagu'];
    $absorbed += $item['absorbed'];
}

$money = static function (float $value): string {
    if ($value >= 1e9) {
        return 'Rp ' . number_format($value / 1e9, 2, ',', '.') . ' miliar';
    }
    return 'Rp ' . number_format($value / 1e6, 2, ',', '.') . ' juta';
};

$rising = [];
foreach ($latest['items'] as $item) {
    if ($item['delta'] !== null && $item['delta'] > 0) {
        $rising[] = $item;
    }
}
usort($rising, static fn(array $a, array $b): int => $b['delta'] <=> $a['delta']);

$biggest = $rising[0] ?? null;

$opening = $chronological[0]['items'];
$growth = [];
foreach ($latest['items'] as $index => $item) {
    $first = $opening[$index] ?? null;
    if ($first === null) {
        continue;
    }
    $growth[] = [
        'label' => $item['label'],
        'from' => $first['percent'],
        'to' => $item['percent'],
        'delta' => $item['percent'] - $first['percent'],
    ];
}

$months = array_map(static fn(array $p): string => $p['label'], $chronological);
$shortMonths = array_map(
    static fn(string $label): string => substr(str_replace('Periode ', '', $label), 0, 3),
    $months
);

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fmtPercent = static fn(float $value): string => number_format($value, 2, ',', '.') . '%';
$fmtPoints = static fn(float $value): string => ($value > 0 ? '+' : ($value < 0 ? '−' : ''))
    . number_format(abs($value), 2, ',', '.');

/* ---- Grafik: dua garis (DIPA 01, DIPA 03) di atas skala 0–100%. ---- */
// Lebar SVG = lebar isi kartu (1080 - 2*72 padding body - 2*24 padding panel). Angka
// 936 akan melewati panel dan memotong label bulan terakhir di tepi kanan.
$chartWidth = 888;
$chartHeight = 356;
// padX menampung label sumbu ("100%") yang ditulis rata kanan di kiri grafik.
$padX = 56;
$padY = 22;
$usableWidth = $chartWidth - $padX * 2;
$usableHeight = $chartHeight - $padY * 2;
$pointFor = static function (float $percent, int $index) use ($padX, $padY, $usableWidth, $usableHeight, $chronological): array {
    $step = count($chronological) > 1 ? $usableWidth / (count($chronological) - 1) : 0;
    return [
        $padX + $step * $index,
        $padY + $usableHeight - ($percent / 100) * $usableHeight,
    ];
};

$svgLines = '';
$palette = ['DIPA 01' => '#8f1f0b', 'DIPA 03' => '#b98f24'];
foreach ($series as $label => $points) {
    $color = $palette[$label] ?? '#2f140e';
    $coordinates = [];
    foreach ($points as $index => $point) {
        [$x, $y] = $pointFor($point['percent'], $index);
        $coordinates[] = round($x, 1) . ',' . round($y, 1);
    }
    $svgLines .= '<polyline fill="none" stroke="' . $color . '" stroke-width="4" stroke-linecap="round" '
        . 'stroke-linejoin="round" points="' . implode(' ', $coordinates) . '"/>';
    $lastIndex = count($points) - 1;
    [$lastX, $lastY] = $pointFor($points[$lastIndex]['percent'], $lastIndex);
    $svgLines .= '<circle cx="' . round($lastX, 1) . '" cy="' . round($lastY, 1) . '" r="8" fill="#fff" stroke="'
        . $color . '" stroke-width="4"/>';
}

$svgGrid = '';
foreach ([0, 25, 50, 75, 100] as $level) {
    $y = $padY + $usableHeight - ($level / 100) * $usableHeight;
    $svgGrid .= '<line x1="' . $padX . '" y1="' . round($y, 1) . '" x2="' . ($chartWidth - $padX)
        . '" y2="' . round($y, 1) . '" stroke="rgba(47,20,14,.10)" stroke-width="1"/>';
    $svgGrid .= '<text x="' . ($padX - 12) . '" y="' . round($y + 4, 1)
        . '" text-anchor="end" font-size="13" fill="#a1927f">' . $level . '%</text>';
}
$svgMonths = '';
foreach ($shortMonths as $index => $month) {
    $step = count($shortMonths) > 1 ? $usableWidth / (count($shortMonths) - 1) : 0;
    $x = $padX + $step * $index;
    $svgMonths .= '<text x="' . round($x, 1) . '" y="' . ($chartHeight - 4)
        . '" text-anchor="middle" font-size="15" font-weight="700" fill="#8a7a6b">' . $escape($month) . '</text>';
}

/* ---- Kartu ringkas per jenis DIPA. ---- */
$cards = '';
foreach ($latest['items'] as $item) {
    $delta = $item['delta'];
    $trend = $delta === null ? 'flat' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'));
    $deltaHtml = $delta === null
        ? '<span class="delta delta--flat">periode awal</span>'
        : '<span class="delta delta--' . $trend . '">'
            . ($trend === 'up' ? '&#9650; ' : ($trend === 'down' ? '&#9660; ' : ''))
            . $fmtPoints($delta) . ' poin</span>';
    // "Sebelumnya -> sekarang": pembanding diambil dari panel bulan sebelumnya pada
    // modul, jadi angka di kartu tidak perlu diketik ulang.
    $previousPercent = null;
    // Periode paling awal tidak punya pembanding; $previous tidak didefinisikan di sana.
    foreach (($previous['items'] ?? []) as $candidate) {
        if ($candidate['label'] === $item['label']) {
            $previousPercent = $candidate['percent'];
            break;
        }
    }
    $pathHtml = $previousPercent === null
        ? ''
        : '<p class="path">' . $escape(number_format($previousPercent, 2, ',', '.')) . '%'
            . ' <em>&rarr;</em> <strong>' . $escape($item['percent_text']) . '</strong></p>';
    $cards .= <<<HTML
      <article class="card">
        <div class="card-head">
          <span class="card-label">{$escape($item['label'])}</span>
          {$deltaHtml}
        </div>
        <div class="card-body">
          <span class="card-value">{$escape($item['percent_text'])}</span>
          <span class="ring" style="--pct:{$item['percent']}"><span class="ring-inner">{$escape(number_format($item['percent'], 0, ',', '.'))}%</span></span>
        </div>
        {$pathHtml}
        <dl>
          <div><dt>Pagu</dt><dd>{$escape($money($item['pagu']))}</dd></div>
          <div><dt>Terserap</dt><dd>{$escape($money($item['absorbed']))}</dd></div>
        </dl>
      </article>
HTML;
}

$highlights = '';
if ($biggest !== null) {
    $highlights .= '<li><strong>' . $escape($biggest['label']) . '</strong> naik '
        . $fmtPoints($biggest['delta']) . ' poin — kenaikan terbesar bulan ini.</li>';
}
$highlights .= '<li>Total terserap <strong>' . $escape($money($absorbed)) . '</strong> dari pagu '
    . $escape($money($pagu)) . ', sisa ' . $escape($money($pagu - $absorbed)) . '.</li>';
foreach ($growth as $entry) {
    if (abs($entry['delta']) < 0.005) {
        continue;
    }
    $highlights .= '<li>' . $escape($entry['label']) . ' sejak ' . $escape($shortMonths[0]) . ': '
        . $fmtPoints($entry['delta']) . ' poin (' . $escape($fmtPercent($entry['from']))
        . ' &rarr; ' . $escape($fmtPercent($entry['to'])) . ').</li>';
}

$periodLabel = $latest['label'];
$slug = preg_replace('~[^0-9a-z]+~i', '-', $periodLabel);
// Interpolasi string PHP tidak menerima ternary; kalimatnya disusun di sini.
$leadText = 'Penyerapan anggaran per ' . $periodLabel
    . ($hasPrevious ? ', dibanding bulan sebelumnya' : '') . '.';
$out = $options['out'] ?? ($root . '/tmp/social/dipa-ig-' . preg_replace('~[^0-9a-z]+~i', '-', $periodLabel) . '.html');
$captionPath = $options['caption'] ?? ($root . '/tmp/social/dipa-ig-' . preg_replace('~[^0-9a-z]+~i', '-', $periodLabel) . '.md');
foreach ([dirname($out), dirname($captionPath)] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "Tidak bisa membuat folder {$directory}\n");
        exit(1);
    }
}
if ($out === '' || $captionPath === '') {
    fwrite(STDERR, "Path output kosong.\n");
    exit(1);
}

/* ---------- Rincian per akun (slide 2) ---------- */
/*
 * Modul 816 hanya menyimpan dua baris (DIPA 01 dan DIPA 03); rincian "apa yang paling
 * banyak terserap" ada di PDF laporan. Ekstraksinya dikerjakan tools/extract-dipa-detail.py
 * (pymupdf) dan hasilnya di-cache per periode, karena PDF di Drive tidak berubah.
 */
$detail = null;
$detailNote = '';
if (!isset($options['no-detail'])) {
    $python = (string) ($options['python'] ?? getenv('PN_NATUNA_PYTHON') ?: '');
    $usable = null;
    foreach (array_filter([$python, 'python3', 'python']) as $candidate) {
        $probe = [];
        $code = 1;
        exec(escapeshellarg($candidate) . ' -c "import pymupdf" 2>&1', $probe, $code);
        if ($code === 0) {
            $usable = $candidate;
            break;
        }
    }
    $detailPath = dirname($out) . '/dipa-detail-' . $slug . '.json';
    if ($usable === null) {
        $detailNote = 'Python + pymupdf tidak ditemukan; slide rincian dilewati. Pakai --python=<path>.';
    } elseif (($latest['link'] ?? '') === '') {
        $detailNote = "Panel {$periodLabel} tidak memuat tautan PDF; slide rincian dilewati.";
    } else {
        if (isset($options['refresh-detail']) || !is_file($detailPath)) {
            $command = escapeshellarg($usable) . ' ' . escapeshellarg($root . '/tools/extract-dipa-detail.py')
                . ' --url ' . escapeshellarg((string) $latest['link'])
                . ' --out ' . escapeshellarg($detailPath);
            $output = [];
            $status = 1;
            exec($command . ' 2>&1', $output, $status);
            if ($status !== 0) {
                $detailNote = 'Ekstraksi rincian gagal: ' . implode(' ', array_slice($output, -2));
            }
        }
        if (is_file($detailPath)) {
            $detail = json_decode((string) file_get_contents($detailPath), true) ?: null;
        }
    }
}

/** Rupiah ringkas untuk poster: 4,14 M / 138,58 jt / 9,3 rb. */
$compact = static function (float $value): string {
    if ($value >= 1e9) {
        return 'Rp ' . number_format($value / 1e9, 2, ',', '.') . ' M';
    }
    if ($value >= 1e6) {
        return 'Rp ' . number_format($value / 1e6, 2, ',', '.') . ' jt';
    }
    if ($value >= 1e3) {
        return 'Rp ' . number_format($value / 1e3, 1, ',', '.') . ' rb';
    }
    return 'Rp ' . number_format($value, 0, ',', '.');
};

/*
 * Periode dengan satu titik tidak punya tren: grafiknya kosong dan menyisakan
 * sepertiga kanvas. Untuk itu panel grafik diganti komposisi lima akun terbesar,
 * yang tetap informatif dan memakai data rincian yang sama.
 */
if (count($chronological) < 3 && $detail !== null && !empty($detail['items'])) {
    $composition = '';
    $compositionTotal = max(1.0, (float) $detail['total_realisasi']);
    foreach (array_slice($detail['items'], 0, 8) as $item) {
        $share = (float) $item['realisasi'] / $compositionTotal * 100;
        $composition .= '<li class="comp"><span class="comp-name">' . $escape($item['label']) . '</span>'
            . '<span class="comp-bar"><i style="width:' . round($share, 1) . '%"></i></span>'
            . '<span class="comp-value">' . $escape(number_format($share, 1, ',', '.')) . '%</span></li>';
    }
    $chartPanel = '<div class="panel"><h2>Delapan akun terbesar</h2>'
        . '<p class="panel-note">Porsi terhadap total penyerapan '
        . $escape($compact((float) $detail['total_realisasi'])) . '</p>'
        . '<ul class="comp-list">' . $composition . '</ul></div>';
} else {
    $chartPanel = '<div class="panel"><h2>Tren Penyerapan ' . $escape($shortMonths[0]) . ' &ndash; '
        . $escape(end($shortMonths)) . '</h2>'
        . '<div class="legend"><span><i style="background:#8f1f0b"></i>DIPA 01</span>'
        . '<span><i style="background:#b98f24"></i>DIPA 03</span></div>'
        . '<svg width="' . $chartWidth . '" height="' . $chartHeight . '" viewBox="0 0 ' . $chartWidth . ' ' . $chartHeight
        . '" role="img" aria-label="Tren penyerapan DIPA 01 dan DIPA 03">' . $svgGrid . $svgLines . $svgMonths . '</svg></div>';
}

$html = <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Realisasi DIPA {$escape($periodLabel)} — PN Natuna</title>
<style>
@font-face { font-family: 'Fraunces'; src: url('/templates/pn_natuna_2026/fonts/fraunces-var.woff2') format('woff2'); font-weight: 300 900; font-display: block; }
@font-face { font-family: 'Jakarta'; src: url('/templates/pn_natuna_2026/fonts/plus-jakarta-sans-var.woff2') format('woff2'); font-weight: 200 800; font-display: block; }
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { width: 1080px; }
body {
  height: 1350px;
  font-family: 'Jakarta', system-ui, sans-serif;
  color: #1d2730;
  background:
    radial-gradient(circle at 92% 4%, rgba(185,143,36,.20), transparent 44%),
    radial-gradient(circle at 0% 98%, rgba(143,31,11,.14), transparent 48%),
    linear-gradient(164deg, #fdfaf3 0%, #f7f1e4 100%);
  padding: 52px 72px 42px;
  display: flex; flex-direction: column;
}
.brand { display: flex; align-items: center; gap: 20px; }
.brand img { width: 76px; height: auto; }
.brand .inst { font-size: 15px; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; color: #8f1f0b; }
.brand p { font-size: 25px; font-weight: 800; color: #2f140e; margin-top: 4px; }
.brand small { display: block; margin-top: 3px; font-size: 15px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a7a6b; }
.kicker { margin-left: auto; padding: 9px 20px; border-radius: 999px; background: #8f1f0b; color: #fdf6e8; font-size: 15px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
h1 { font-family: 'Fraunces', Georgia, serif; font-size: 66px; font-weight: 700; line-height: 1.04; letter-spacing: -.02em; color: #2f140e; margin-top: 28px; }
h1 span { color: #8f1f0b; }
.lead { margin-top: 10px; font-size: 21px; font-weight: 600; color: #5a4a3d; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 26px; }
.card { background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 20px 22px; box-shadow: 0 10px 24px rgba(47,20,14,.07); }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.card-label { font-size: 16px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8a7a6b; }
.card-body { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 12px; }
.card-value { font-family: 'Fraunces', Georgia, serif; font-size: 58px; font-weight: 700; line-height: 1; color: #2f140e; }
.ring { width: 96px; height: 96px; border-radius: 50%; flex: 0 0 96px; display: grid; place-items: center;
  background: conic-gradient(#8f1f0b calc(var(--pct) * 1%), #efe7d8 0); }
.ring-inner { width: 74px; height: 74px; border-radius: 50%; background: #fff; display: grid; place-items: center;
  font-size: 17px; font-weight: 800; color: #671607; }
.path { margin-top: 12px; font-size: 17px; font-weight: 700; color: #8a7a6b; display: flex; align-items: center; gap: 8px; }
.path strong { color: #1f5b4b; font-size: 19px; }
.path em { font-style: normal; color: #b98f24; }
dl { display: flex; gap: 18px; margin-top: 12px; }
dt { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #a1927f; }
dd { font-size: 18px; font-weight: 800; color: #3a2c22; margin-top: 3px; }
.delta { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 999px; font-size: 14px; font-weight: 800; white-space: nowrap; }
.delta--up { background: rgba(31,91,75,.12); color: #1f5b4b; }
.delta--down { background: rgba(143,31,11,.12); color: #8f1f0b; }
.delta--flat { background: rgba(29,39,48,.08); color: #6b7280; }
.panel { margin-top: 24px; background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 20px 22px 12px; box-shadow: 0 10px 24px rgba(47,20,14,.06); }
.panel h2 { font-size: 15px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #671607; }
.panel-note { margin-top: 6px; font-size: 14px; font-weight: 600; color: #8a7a6b; }
.comp-list { list-style: none; display: grid; gap: 20px; margin-top: 20px; padding-bottom: 6px; }
.comp { display: grid; grid-template-columns: 1fr 150px 54px; align-items: center; gap: 14px; }
.comp-name { font-size: 18px; font-weight: 700; color: #2f140e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.comp-bar { display: block; height: 9px; border-radius: 999px; background: #f0e9da; overflow: hidden; }
.comp-bar i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8f1f0b, #c0603f); }
.comp-value { font-family: 'Fraunces', Georgia, serif; font-size: 20px; font-weight: 700; color: #671607; text-align: right; }
.legend { display: flex; gap: 18px; margin-top: 7px; font-size: 13px; font-weight: 700; color: #6b5a4c; }
.legend i { display: inline-block; width: 14px; height: 4px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
ul { margin-top: 22px; display: grid; gap: 11px; list-style: none; }
li { font-size: 18px; font-weight: 600; color: #4a3a2f; padding-left: 22px; position: relative; line-height: 1.35; }
li::before { content: ''; position: absolute; left: 3px; top: 8px; width: 7px; height: 7px; border-radius: 50%; background: #b98f24; }
li strong { color: #2f140e; font-weight: 800; }
.foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 2px solid rgba(143,31,11,.16); padding-top: 14px; font-size: 16px; font-weight: 700; color: #7a6a5c; }
.foot strong { color: #8f1f0b; }
</style>
</head>
<body>
  <div class="brand">
    <img src="/images/brand/logo-pn-natuna.webp" alt="">
    <div>
      <p class="inst">Mahkamah Agung Republik Indonesia</p>
      <p>Pengadilan Negeri Natuna</p>
      <small>Kelas II</small>
    </div>
    <span class="kicker">s.d. {$escape($periodLabel)}</span>
  </div>

  <h1>Realisasi <span>DIPA</span> 2026</h1>
  <p class="lead">{$escape($leadText)}</p>

  <div class="grid">
{$cards}
  </div>

  {$chartPanel}

  <ul>
{$highlights}
  </ul>

  <div class="foot">
    <span>Sumber: laporan realisasi anggaran DIPA {$escape($periodLabel)}</span>
    <strong>pn-natuna.go.id</strong>
  </div>
</body>
</html>
HTML;

if (file_put_contents($out, $html) === false) {
    fwrite(STDERR, "Gagal menulis {$out}\n");
    exit(1);
}

/* ---------- Slide 2: rincian 10 akun terbesar ---------- */
$detailOut = '';
if ($detail !== null && !empty($detail['items'])) {
    $rows = '';
    $rank = 0;
    foreach ($detail['items'] as $item) {
        $rank++;
        $percent = (float) $item['persen'];
        $itemGrowth = (float) $item['kenaikan'];
        $trend = $itemGrowth > 0 ? 'up' : ($itemGrowth < 0 ? 'down' : 'flat');
        $barWidth = min(100, max(0, $percent));
        $rows .= '<li class="row">'
            . '<span class="rank">' . $rank . '</span>'
            . '<span class="body">'
            . '<span class="name">' . $escape($item['label']) . '</span>'
            . '<span class="amounts">' . $escape($compact((float) $item['sebelumnya']))
            . ' <em>&rarr;</em> <strong>' . $escape($compact((float) $item['realisasi'])) . '</strong></span>'
            . '<span class="bar"><i style="width:' . round($barWidth, 1) . '%"></i></span>'
            . '</span>'
            . '<span class="pct">' . $escape(number_format($percent, 2, ',', '.')) . '%'
            . '<span class="pct-delta delta--' . $trend . '">'
            . ($itemGrowth > 0 ? '+' : ($itemGrowth < 0 ? '&minus;' : ''))
            . ($itemGrowth == 0.0 ? 'stabil' : $escape($compact(abs($itemGrowth)))) . '</span></span>'
            . '</li>';
    }
    $detailTotal = (float) $detail['total_realisasi'];
    $detailPagu = (float) $detail['total_pagu'];
    $detailPrev = (float) $detail['total_sebelumnya'];
    $detailOut = dirname($out) . '/dipa-ig-' . $slug . '-rincian.html';
    $detailHtml = <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Rincian DIPA {$escape($periodLabel)} — PN Natuna</title>
<style>
@font-face { font-family: 'Fraunces'; src: url('/templates/pn_natuna_2026/fonts/fraunces-var.woff2') format('woff2'); font-weight: 300 900; font-display: block; }
@font-face { font-family: 'Jakarta'; src: url('/templates/pn_natuna_2026/fonts/plus-jakarta-sans-var.woff2') format('woff2'); font-weight: 200 800; font-display: block; }
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { width: 1080px; }
body {
  height: 1350px;
  font-family: 'Jakarta', system-ui, sans-serif;
  color: #1d2730;
  background:
    radial-gradient(circle at 94% 3%, rgba(185,143,36,.18), transparent 40%),
    linear-gradient(166deg, #fdfaf3 0%, #f6f0e2 100%);
  padding: 48px 64px 38px;
  display: flex; flex-direction: column;
}
.brand { display: flex; align-items: center; gap: 16px; }
.brand img { width: 60px; }
.brand .inst { font-size: 12.5px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; color: #8f1f0b; }
.brand p { font-size: 20px; font-weight: 800; color: #2f140e; margin-top: 3px; }
.brand small { display: block; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a7a6b; margin-top: 2px; }
.kicker { margin-left: auto; padding: 7px 16px; border-radius: 999px; background: #8f1f0b; color: #fdf6e8; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
h1 { font-family: 'Fraunces', Georgia, serif; font-size: 46px; font-weight: 700; line-height: 1.06; letter-spacing: -.015em; color: #2f140e; margin-top: 20px; }
h1 span { color: #8f1f0b; }
.lead { margin-top: 6px; font-size: 17px; font-weight: 600; color: #5a4a3d; }
.summary { display: flex; gap: 14px; margin-top: 16px; }
.summary div { flex: 1; background: #fff; border: 1px solid #e6dcc9; border-radius: 14px; padding: 12px 16px; }
.summary dt { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #a1927f; }
.summary dd { font-size: 22px; font-weight: 800; color: #2f140e; margin-top: 4px; font-family: 'Fraunces', Georgia, serif; }
.summary small { display: block; font-size: 12px; font-weight: 700; color: #8a7a6b; margin-top: 3px; }
ol.rows { list-style: none; margin-top: 20px; display: grid; gap: 11px; }
.row { display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #ece3d4; border-radius: 12px; padding: 13px 16px; }
.rank { flex: 0 0 26px; font-family: 'Fraunces', Georgia, serif; font-size: 17px; font-weight: 700; color: #b98f24; text-align: center; }
.body { flex: 1; min-width: 0; display: block; }
.name { display: block; font-size: 15px; font-weight: 700; color: #2f140e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.amounts { display: block; font-size: 13px; font-weight: 600; color: #8a7a6b; margin-top: 2px; }
.amounts strong { color: #1f5b4b; font-weight: 800; }
.amounts em { font-style: normal; color: #b98f24; }
.bar { display: block; height: 5px; border-radius: 999px; background: #f0e9da; margin-top: 5px; overflow: hidden; }
.bar i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8f1f0b, #c0603f); }
.pct { flex: 0 0 108px; text-align: right; font-family: 'Fraunces', Georgia, serif; font-size: 21px; font-weight: 700; color: #2f140e; }
.pct-delta { display: block; font-family: 'Jakarta', sans-serif; font-size: 11.5px; font-weight: 800; border-radius: 999px; padding: 2px 0; margin-top: 3px; }
.pct-delta.delta--up { color: #1f5b4b; background: rgba(31,91,75,.10); }
.pct-delta.delta--down { color: #8f1f0b; background: rgba(143,31,11,.10); }
.pct-delta.delta--flat { color: #6b7280; background: rgba(29,39,48,.07); }
.foot { margin-top: auto; display: flex; justify-content: space-between; border-top: 2px solid rgba(143,31,11,.16); padding-top: 12px; font-size: 13px; font-weight: 700; color: #7a6a5c; }
.foot strong { color: #8f1f0b; }
</style>
</head>
<body>
  <div class="brand">
    <img src="/images/brand/logo-pn-natuna.webp" alt="">
    <div>
      <p class="inst">Mahkamah Agung Republik Indonesia</p>
      <p>Pengadilan Negeri Natuna</p>
      <small>Kelas II</small>
    </div>
    <span class="kicker">Rincian {$escape($periodLabel)}</span>
  </div>

  <h1>10 akun <span>terbesar</span></h1>
  <p class="lead">Realisasi kumulatif {$escape($periodLabel)}: sebelumnya &rarr; sekarang.</p>

  <div class="summary">
    <div><dt>Terserap</dt><dd>{$escape($compact($detailTotal))}</dd><small>dari pagu {$escape($compact($detailPagu))}</small></div>
    <div><dt>Bulan ini</dt><dd>{$escape($compact($detailTotal - $detailPrev))}</dd><small>sebelumnya {$escape($compact($detailPrev))}</small></div>
    <div><dt>Akun</dt><dd>{$escape((string) $detail['akun'])}</dd><small>akun belanja</small></div>
  </div>

  <ol class="rows">
{$rows}
  </ol>

  <div class="foot">
    <span>Sumber: laporan realisasi SP2D TA 2026 — {$escape($periodLabel)}</span>
    <strong>pn-natuna.go.id</strong>
  </div>
</body>
</html>
HTML;
    if (file_put_contents($detailOut, $detailHtml) === false) {
        fwrite(STDERR, "Gagal menulis {$detailOut}\n");
        $detailOut = '';
    }
}


$caption = "# Garis besar postingan Instagram — Realisasi DIPA {$periodLabel}\n\n"
    . "**Visual:** dua slide 1080x1350.\n"
    . "- Slide 1 — kartu DIPA 01 & DIPA 03 dengan cincin persen dan selisih poin, "
    . "grafik tren {$shortMonths[0]}–" . end($shortMonths) . ", sorotan.\n"
    . ($detailOut !== '' ? "- Slide 2 — 10 akun belanja terbesar: sebelumnya \u{2192} sekarang.\n" : '')
    . "\n## Angka utama\n";
foreach ($latest['items'] as $item) {
    $caption .= '- **' . $item['label'] . '**: ' . $item['percent_text'];
    if ($item['delta'] !== null) {
        $caption .= ' (' . $fmtPoints($item['delta']) . ' poin dari bulan sebelumnya)';
    }
    $caption .= ' — terserap ' . $money($item['absorbed']) . ' dari pagu ' . $money($item['pagu']) . "\n";
}
$caption .= '- **Total**: terserap ' . $money($absorbed) . ' dari pagu ' . $money($pagu)
    . ', sisa ' . $money($pagu - $absorbed) . "\n\n";

if ($detail !== null && !empty($detail['items'])) {
    $caption .= "## 10 akun terbesar (sebelumnya -> sekarang)\n";
    $rank = 0;
    foreach ($detail['items'] as $item) {
        $rank++;
        $caption .= $rank . '. ' . $item['label'] . ': '
            . $money((float) $item['sebelumnya']) . ' -> ' . $money((float) $item['realisasi'])
            . ' (' . number_format((float) $item['persen'], 2, ',', '.') . '%';
        if ((float) $item['kenaikan'] > 0) {
            $caption .= ', +' . $money((float) $item['kenaikan']) . ' bulan ini';
        }
        $caption .= ")\n";
    }
    $caption .= '- Total ' . $detail['akun'] . ' akun: ' . $money((float) $detail['total_realisasi'])
        . ' dari pagu ' . $money((float) $detail['total_pagu']) . "\n\n";
}

$caption .= "## Sorotan\n";
foreach ($rising as $item) {
    $caption .= '- ' . $item['label'] . ' naik ' . $fmtPoints($item['delta']) . " poin\n";
}
$caption .= "\n## Alur unggahan\n"
    . "1. Slide 1: poster utama.\n"
    . ($detailOut !== '' ? "2. Slide 2: rincian 10 akun terbesar.\n" : '')
    . ($detailOut !== '' ? "3. Slide 3: dokumen laporan DIPA resmi (tautan Drive).\n" : "2. Slide 2: dokumen laporan DIPA resmi (tautan Drive).\n")
    . "\n## Keterangan\n"
    . "Realisasi anggaran DIPA {$periodLabel}, Pengadilan Negeri Natuna. "
    . "Rincian laporan: pn-natuna.go.id #DIPA #TransparansiAnggaran\n";

if (file_put_contents($captionPath, $caption) === false) {
    fwrite(STDERR, "Gagal menulis {$captionPath}\n");
    exit(1);
}


/* ------------------------------------------------------------------ */
/* Animasi (Reels). Parameter `?t=<detik>` menggambar keadaan tepat pada detik itu,
   sehingga tiap frame deterministik: video bisa dirender ulang kapan pun dan tidak
   bergantung pada kecepatan mesin saat merekam. */
/* ------------------------------------------------------------------ */

$videoPath = $options['video'] ?? ($root . '/tmp/social/dipa-ig-' . preg_replace('~[^0-9a-z]+~i', '-', $periodLabel) . '-video.html');

$previousPercentFor = static function (string $label) use ($previous): ?float {
    foreach (($previous['items'] ?? []) as $candidate) {
        if ($candidate['label'] === $label) {
            return (float) $candidate['percent'];
        }
    }
    return null;
};

$motion = [
    'duration' => 8.0,
    'period' => $periodLabel,
    'hasPrevious' => $hasPrevious,
    'pagu' => $pagu,
    'absorbed' => $absorbed,
    'sisa' => $pagu - $absorbed,
    'paguText' => $money($pagu),
    'absorbedText' => $money($absorbed),
    'sisaText' => $money($pagu - $absorbed),
    'items' => array_map(static fn(array $item): array => [
        'label' => $item['label'],
        'percent' => $item['percent'],
        'percentText' => $item['percent_text'],
        'delta' => $item['delta'],
        'deltaText' => $item['delta'] === null ? 'periode awal' : $fmtPoints($item['delta']) . ' poin',
        'paguText' => $money($item['pagu']),
        'absorbedText' => $money($item['absorbed']),
        'previousPercent' => $previousPercentFor($item['label']),
    ], $latest['items']),
    'months' => $shortMonths,
    'series' => $series,
    'highlights' => array_map(static fn(array $entry): string => $entry['label'] . ' sejak '
        . $shortMonths[0] . ': ' . $fmtPoints($entry['delta']) . ' poin (' . $fmtPercent($entry['from'])
        . ' → ' . $fmtPercent($entry['to']) . ')', $growth),
    'biggest' => $biggest === null ? null : [
        'label' => $biggest['label'],
        'deltaText' => $fmtPoints($biggest['delta']) . ' poin',
    ],
];

$motionJson = json_encode($motion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($motionJson === false) {
    fwrite(STDERR, "Gagal menyusun data animasi.\n");
    exit(1);
}

$videoHtml = <<<'HTML'
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Realisasi DIPA — animasi</title>
<style>
@font-face { font-family: 'Fraunces'; src: url('/templates/pn_natuna_2026/fonts/fraunces-var.woff2') format('woff2'); font-weight: 300 900; font-display: block; }
@font-face { font-family: 'Jakarta'; src: url('/templates/pn_natuna_2026/fonts/plus-jakarta-sans-var.woff2') format('woff2'); font-weight: 200 800; font-display: block; }
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { width: 1080px; height: 1350px; overflow: hidden; }
body {
  font-family: 'Jakarta', system-ui, sans-serif;
  color: #1d2730;
  background:
    radial-gradient(circle at 92% 4%, rgba(185,143,36,.20), transparent 44%),
    radial-gradient(circle at 0% 98%, rgba(143,31,11,.14), transparent 48%),
    linear-gradient(164deg, #fdfaf3 0%, #f7f1e4 100%);
  padding: 56px 72px 44px;
  display: flex; flex-direction: column;
}
.will { opacity: 0; }
.brand { display: flex; align-items: center; gap: 20px; }
.brand img { width: 80px; height: auto; }
.brand .inst { font-size: 15px; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; color: #8f1f0b; }
.brand p { font-size: 25px; font-weight: 800; color: #2f140e; margin-top: 4px; }
.brand small { display: block; margin-top: 3px; font-size: 15px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a7a6b; }
.kicker { margin-left: auto; padding: 9px 20px; border-radius: 999px; background: #8f1f0b; color: #fdf6e8; font-size: 15px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
h1 { font-family: 'Fraunces', Georgia, serif; font-size: 66px; font-weight: 700; line-height: 1.04; letter-spacing: -.02em; color: #2f140e; margin-top: 34px; }
h1 span { color: #8f1f0b; }
.lead { margin-top: 12px; font-size: 22px; font-weight: 600; color: #5a4a3d; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 30px; }
.card { background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 20px 22px; box-shadow: 0 10px 24px rgba(47,20,14,.07); }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.card-label { font-size: 16px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8a7a6b; }
.card-body { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 12px; }
.card-value { font-family: 'Fraunces', Georgia, serif; font-size: 58px; font-weight: 700; line-height: 1; color: #2f140e; }
.ring { width: 96px; height: 96px; border-radius: 50%; flex: 0 0 96px; display: grid; place-items: center; background: conic-gradient(#8f1f0b calc(var(--pct) * 1%), #efe7d8 0); }
.ring-inner { width: 74px; height: 74px; border-radius: 50%; background: #fff; display: grid; place-items: center; font-size: 17px; font-weight: 800; color: #671607; }
.path { margin-top: 12px; font-size: 17px; font-weight: 700; color: #8a7a6b; display: flex; align-items: center; gap: 8px; }
.path strong { color: #1f5b4b; font-size: 19px; }
.path em { font-style: normal; color: #b98f24; }
dl { display: flex; gap: 22px; margin-top: 14px; }
dt { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #a1927f; }
dd { font-size: 19px; font-weight: 800; color: #3a2c22; margin-top: 3px; }
.delta { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 999px; font-size: 15px; font-weight: 800; white-space: nowrap; }
.delta--up { background: rgba(31,91,75,.12); color: #1f5b4b; }
.delta--flat { background: rgba(29,39,48,.08); color: #6b7280; }
.panel { margin-top: 30px; background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 22px 24px 16px; box-shadow: 0 10px 24px rgba(47,20,14,.06); }
.panel h2 { font-size: 16px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #671607; }
.legend { display: flex; gap: 20px; margin-top: 8px; font-size: 14px; font-weight: 700; color: #6b5a4c; }
.legend i { display: inline-block; width: 14px; height: 4px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
ul { margin-top: 26px; display: grid; gap: 12px; list-style: none; }
li { font-size: 19px; font-weight: 600; color: #4a3a2f; padding-left: 24px; position: relative; line-height: 1.35; }
li::before { content: ''; position: absolute; left: 4px; top: 9px; width: 7px; height: 7px; border-radius: 50%; background: #b98f24; }
li strong { color: #2f140e; font-weight: 800; }
.foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 2px solid rgba(143,31,11,.16); padding-top: 16px; font-size: 17px; font-weight: 700; color: #7a6a5c; }
.foot strong { color: #8f1f0b; }
</style>
</head>
<body>
  <div class="brand will">
    <img src="/images/brand/logo-pn-natuna.webp" alt="">
    <div>
      <p class="inst">Mahkamah Agung Republik Indonesia</p>
      <p>Pengadilan Negeri Natuna</p>
      <small>Kelas II</small>
    </div>
    <span class="kicker" id="kicker"></span>
  </div>
  <h1 class="will">Realisasi <span>DIPA</span> 2026</h1>
  <p class="lead will" id="lead"></p>
  <div class="grid" id="grid"></div>
  <div class="panel" id="panel">
    <h2 id="panelTitle"></h2>
    <div class="legend">
      <span><i style="background:#8f1f0b"></i>DIPA 01</span>
      <span><i style="background:#b98f24"></i>DIPA 03</span>
    </div>
    <svg width="888" height="356" viewBox="0 0 888 356" id="chart"></svg>
  </div>
  <ul id="highlights"></ul>
  <div class="foot will">
    <span id="footNote"></span>
    <strong>pn-natuna.go.id</strong>
  </div>
<script>
const D = __DATA__;
const W = 888, H = 356, PAD_X = 56, PAD_Y = 22;
const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const ease = (t) => 1 - Math.pow(1 - clamp(t), 3);
const span = (t, start, dur) => ease((t - start) / dur);
const fmt = (v) => v.toFixed(2).replace('.', ',').replace('.', ',');

const grid = document.getElementById('grid');
const cards = D.items.map((item) => {
  const el = document.createElement('article');
  el.className = 'card will';
  const trend = item.delta === null ? 'flat' : (item.delta > 0 ? 'up' : 'flat');
  const pathHtml = item.previousPercent === null ? '' :
    '<p class="path">' + item.previousPercent.toFixed(2).replace('.', ',') + '% ' +
    '<em>\u2192</em> <strong>' + item.percentText + '</strong></p>';
  el.innerHTML =
    '<div class="card-head"><span class="card-label">' + item.label + '</span>' +
    '<span class="delta delta--' + trend + '">' + (trend === 'up' ? '\u25B2 ' : '') + item.deltaText + '</span></div>' +
    '<div class="card-body"><span class="card-value" data-value></span>' +
    '<span class="ring" style="--pct:' + item.percent + '"><span class="ring-inner" data-ring>' +
    Math.round(item.percent) + '%</span></span></div>' + pathHtml +
    '<dl><div><dt>Pagu</dt><dd>' + item.paguText + '</dd></div>' +
    '<div><dt>Terserap</dt><dd>' + item.absorbedText + '</dd></div></dl>';
  grid.appendChild(el);
  return el;
});

const months = D.months;
const xFor = (i) => PAD_X + (months.length > 1 ? (W - PAD_X * 2) / (months.length - 1) : 0) * i;
const yFor = (p) => PAD_Y + (H - PAD_Y * 2) - (p / 100) * (H - PAD_Y * 2);
const palette = { 'DIPA 01': '#8f1f0b', 'DIPA 03': '#b98f24' };

const svg = document.getElementById('chart');
let svgStatic = '';
for (const level of [0, 25, 50, 75, 100]) {
  const y = yFor(level);
  svgStatic += '<line x1="' + PAD_X + '" y1="' + y + '" x2="' + (W - PAD_X) + '" y2="' + y + '" stroke="rgba(47,20,14,.10)"/>';
  svgStatic += '<text x="' + (PAD_X - 12) + '" y="' + (y + 4) + '" text-anchor="end" font-size="13" fill="#a1927f">' + level + '%</text>';
}
months.forEach((m, i) => {
  svgStatic += '<text data-month="' + i + '" x="' + xFor(i) + '" y="' + (H - 4) + '" text-anchor="middle" font-size="15" font-weight="700" fill="#8a7a6b">' + m + '</text>';
});
svg.innerHTML = svgStatic;
const monthLabels = [...svg.querySelectorAll('[data-month]')];
const lines = [];
Object.entries(D.series).forEach(([label, points]) => {
  const line = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
  line.setAttribute('fill', 'none');
  line.setAttribute('stroke', palette[label] || '#2f140e');
  line.setAttribute('stroke-width', '4');
  line.setAttribute('stroke-linecap', 'round');
  line.setAttribute('stroke-linejoin', 'round');
  svg.appendChild(line);
  const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
  dot.setAttribute('r', '8');
  dot.setAttribute('fill', '#fff');
  dot.setAttribute('stroke', palette[label] || '#2f140e');
  dot.setAttribute('stroke-width', '4');
  svg.appendChild(dot);
  lines.push({ line, dot, points });
});

const highlightList = document.getElementById('highlights');
const items = [];
if (D.biggest) {
  items.push('<strong>' + D.biggest.label + '</strong> naik ' + D.biggest.deltaText + ' — kenaikan terbesar bulan ini.');
}
items.push('Total terserap <strong>' + D.absorbedText + '</strong> dari pagu ' + D.paguText + ', sisa ' + D.sisaText + '.');
D.highlights.forEach((h) => items.push(h.replace(/^(\S+ \S+)/, '<strong>$1</strong>')));
const lis = items.map((html) => {
  const li = document.createElement('li');
  li.className = 'will';
  li.innerHTML = html;
  highlightList.appendChild(li);
  return li;
});

document.getElementById('kicker').textContent = 's.d. ' + D.period;
document.getElementById('lead').textContent = 'Penyerapan anggaran per ' + D.period + (D.hasPrevious ? ', dibanding bulan sebelumnya' : '') + '.';
document.getElementById('panelTitle').textContent = 'Tren Penyerapan ' + months[0] + ' – ' + months[months.length - 1];
document.getElementById('footNote').textContent = 'Sumber: laporan realisasi anggaran DIPA ' + D.period;

function reveal(el, p, dy = 16) {
  el.style.opacity = String(p);
  el.style.transform = 'translateY(' + (dy * (1 - p)) + 'px)';
}

window.seek = function (t) {
  reveal(document.querySelector('.brand'), span(t, 0.0, 0.6));
  reveal(document.querySelector('h1'), span(t, 0.25, 0.65), 22);
  reveal(document.getElementById('lead'), span(t, 0.55, 0.5));

  cards.forEach((card, i) => {
    const p = span(t, 0.9 + i * 0.18, 0.6);
    reveal(card, p, 26);
    const value = card.querySelector('[data-value]');
    const item = D.items[i];
    const shown = item.percent * span(t, 1.1 + i * 0.18, 0.9);
    value.textContent = shown.toFixed(2).replace('.', ',') + '%';
    card.querySelector('.ring').style.setProperty('--pct', shown.toFixed(1));
    card.querySelector('[data-ring]').textContent = Math.round(shown) + '%';
    card.querySelector('.delta').style.opacity = String(span(t, 1.7 + i * 0.18, 0.35));
    const path = card.querySelector('.path');
    if (path) path.style.opacity = String(span(t, 1.9 + i * 0.18, 0.4));
  });

  const panelP = span(t, 1.9, 0.6);
  const panel = document.getElementById('panel');
  reveal(panel, panelP, 24);

  const draw = span(t, 2.5, 2.0);
  const total = months.length - 1;
  lines.forEach(({ line, dot, points }) => {
    const shown = Math.max(1, (total + 1) * draw);
    const coords = points.slice(0, Math.ceil(shown)).map((p, i) => [xFor(i), yFor(p.percent)]);
    const lastIdx = Math.floor(shown) - 1;
    const frac = shown - Math.floor(shown);
    let head = null;
    if (lastIdx >= 0 && lastIdx + 1 < points.length && frac > 0) {
      const a = [xFor(lastIdx), yFor(points[lastIdx].percent)];
      const b = [xFor(lastIdx + 1), yFor(points[lastIdx + 1].percent)];
      head = [a[0] + (b[0] - a[0]) * frac, a[1] + (b[1] - a[1]) * frac];
      coords.push(head);
    }
    line.setAttribute('points', coords.map((c) => c[0].toFixed(1) + ',' + c[1].toFixed(1)).join(' '));
    const tip = head || coords[coords.length - 1];
    dot.setAttribute('cx', tip[0].toFixed(1));
    dot.setAttribute('cy', tip[1].toFixed(1));
    dot.setAttribute('opacity', String(clamp(draw * 2)));
  });

  monthLabels.forEach((label, i) => {
    label.setAttribute('opacity', String(span(t, 2.6 + i * 0.12, 0.3)));
  });

  lis.forEach((li, i) => {
    reveal(li, span(t, 4.7 + i * 0.35, 0.5), 14);
  });
  reveal(document.querySelector('.foot'), span(t, 6.2, 0.6));
};

// Jembatan untuk alat perekam: alat penyadap (CDP, ekstensi, atau skrip otomasi)
// kadang berjalan di world terpisah sehingga `window.seek` tidak terlihat. Event DOM
// selalu menyeberang, jadi frame bisa diminta lewat CustomEvent juga.
document.addEventListener('seek', (event) => window.seek(Number(event.detail) || 0));
document.documentElement.setAttribute('data-seek', 'ready');

const params = new URLSearchParams(location.search);
if (params.has('t')) {
  window.seek(parseFloat(params.get('t')) || 0);
  document.documentElement.setAttribute('data-frame', 't');
} else if (params.has('frame')) {
  // Mode perekaman: tanpa loop animasi, frame hanya berubah saat diminta lewat
  // event `seek`. Tanpa ini, requestAnimationFrame terus menimpa frame yang diminta
  // dan hasil rekaman menangkap waktu yang acak.
  window.seek(0);
  document.documentElement.setAttribute('data-frame', 'manual');
} else {
  const start = performance.now();
  const tick = (now) => {
    window.seek(((now - start) / 1000) % (D.duration + 0.8));
    requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}
</script>
</body>
</html>
HTML;

$videoHtml = str_replace('__DATA__', $motionJson, $videoHtml);
if (file_put_contents($videoPath, $videoHtml) === false) {
    fwrite(STDERR, "Gagal menulis {$videoPath}\n");
    exit(1);
}

// Baris pertama sengaja mesin-baca: tools/render-social.py butuh slug untuk
// menemukan berkas video/PNG periode yang dipilih lewat --period.
printf("SLUG=%s\n", $slug);
printf(
    "%s\n%s\n%d periode, %d jenis DIPA, terserap %s dari %s\n",
    $out,
    $captionPath,
    count($chronological),
    count($latest['items']),
    $money($absorbed),
    $money($pagu)
);
printf("%s (durasi %.1f dtk, frame deterministik lewat ?t=)\n", $videoPath, $motion['duration']);
if ($detailOut !== '') {
    printf("%s (rincian %d akun terbesar)\n", $detailOut, count($detail['items']));
} elseif ($detailNote !== '') {
    fwrite(STDERR, "Catatan: {$detailNote}\n");
}
