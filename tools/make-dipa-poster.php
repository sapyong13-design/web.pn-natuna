<?php
/**
 * Poster Instagram portrait (1080x1350) berisi realisasi anggaran DIPA saja.
 *
 * Sumber angka: modul Joomla id 816 (`Kinerja & Akuntabilitas`) — panel `dipa-panel`
 * yang sama dengan yang tampil di beranda, jadi poster tidak pernah memuat angka
 * berbeda dari situs. Tidak ada angka yang ditulis manual di skrip ini.
 *
 * Pemakaian:
 *   php tools/make-dipa-poster.php                 # poster + caption + animasi ke tmp/social/
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

$options = getopt('', ['out::', 'caption::', 'video::', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "php tools/make-dipa-poster.php [--out=path] [--caption=path] [--video=path]\n");
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
    $periods[] = [
        'label' => str_replace('Periode ', '', $text($find('dipa-period', $panel)->item(0))),
        'items' => $items,
    ];
}

if (count($periods) < 2) {
    fwrite(STDERR, "Panel DIPA tidak ditemukan di modul 816.\n");
    exit(1);
}

// Modul menyimpan periode terbaru lebih dulu; poster butuh urutan maju.
$chronological = array_reverse($periods);
$latest = $periods[0];
$previous = $periods[1];

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
    $cards .= <<<HTML
      <article class="card">
        <header>
          <p class="card-label">{$escape($item['label'])}</p>
          {$deltaHtml}
        </header>
        <p class="card-value">{$escape($item['percent_text'])}</p>
        <div class="meter"><i style="width:{$item['percent']}%"></i></div>
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
    $highlights .= '<li>' . $escape($entry['label']) . ' sejak ' . $escape($shortMonths[0]) . ': '
        . $fmtPoints($entry['delta']) . ' poin (' . $escape($fmtPercent($entry['from']))
        . ' &rarr; ' . $escape($fmtPercent($entry['to'])) . ').</li>';
}

$periodLabel = $latest['label'];
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
  padding: 56px 72px 44px;
  display: flex; flex-direction: column;
}
.brand { display: flex; align-items: center; gap: 20px; }
.brand img { width: 80px; height: auto; }
.brand p { font-size: 25px; font-weight: 800; color: #2f140e; }
.brand small { display: block; margin-top: 3px; font-size: 16px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a7a6b; }
.kicker { margin-left: auto; padding: 9px 20px; border-radius: 999px; background: #8f1f0b; color: #fdf6e8; font-size: 15px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
h1 { font-family: 'Fraunces', Georgia, serif; font-size: 68px; font-weight: 700; line-height: 1.04; letter-spacing: -.02em; color: #2f140e; margin-top: 34px; }
h1 span { color: #8f1f0b; }
.lead { margin-top: 12px; font-size: 22px; font-weight: 600; color: #5a4a3d; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 30px; }
.card { background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 22px 24px; box-shadow: 0 10px 24px rgba(47,20,14,.07); }
.card header { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.card-label { font-size: 16px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8a7a6b; }
.card-value { font-family: 'Fraunces', Georgia, serif; font-size: 62px; font-weight: 700; line-height: 1; color: #2f140e; margin-top: 12px; }
.meter { height: 10px; border-radius: 999px; background: #efe7d8; overflow: hidden; margin-top: 14px; }
.meter i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8f1f0b, #c0603f); }
dl { display: flex; gap: 22px; margin-top: 14px; }
dt { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #a1927f; }
dd { font-size: 19px; font-weight: 800; color: #3a2c22; margin-top: 3px; }
.delta { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 999px; font-size: 15px; font-weight: 800; white-space: nowrap; }
.delta--up { background: rgba(31,91,75,.12); color: #1f5b4b; }
.delta--down { background: rgba(143,31,11,.12); color: #8f1f0b; }
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
  <div class="brand">
    <img src="/images/brand/logo-pn-natuna.webp" alt="">
    <div>
      <p>Pengadilan Negeri Natuna</p>
      <small>Kelas II</small>
    </div>
    <span class="kicker">s.d. {$escape($periodLabel)}</span>
  </div>

  <h1>Realisasi <span>DIPA</span> 2026</h1>
  <p class="lead">Penyerapan anggaran per {$escape($periodLabel)}, dibanding bulan sebelumnya.</p>

  <div class="grid">
{$cards}
  </div>

  <div class="panel">
    <h2>Tren Penyerapan {$escape($shortMonths[0])} &ndash; {$escape(end($shortMonths))}</h2>
    <div class="legend">
      <span><i style="background:#8f1f0b"></i>DIPA 01</span>
      <span><i style="background:#b98f24"></i>DIPA 03</span>
    </div>
    <svg width="{$chartWidth}" height="{$chartHeight}" viewBox="0 0 {$chartWidth} {$chartHeight}" role="img" aria-label="Tren penyerapan DIPA 01 dan DIPA 03">
      {$svgGrid}
{$svgLines}
      {$svgMonths}
    </svg>
  </div>

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

$caption = "# Garis besar postingan Instagram — Realisasi DIPA {$periodLabel}\n\n"
    . "**Visual:** poster portrait 1080x1350, dua kartu angka di atas (DIPA 01 & DIPA 03), "
    . "grafik tren {$shortMonths[0]}–" . end($shortMonths) . " di tengah, sorotan di bawah.\n\n"
    . "## Angka utama\n";
foreach ($latest['items'] as $item) {
    $caption .= '- ' . $item['label'] . ': ' . $item['percent_text'];
    if ($item['delta'] !== null) {
        $caption .= ' (' . $fmtPoints($item['delta']) . ' poin dari bulan sebelumnya)';
    }
    $caption .= ' — terserap ' . $money($item['absorbed']) . ' dari pagu ' . $money($item['pagu']) . "\n";
}
$caption .= "- Total: terserap " . $money($absorbed) . " dari pagu " . $money($pagu) . ', sisa ' . $money($pagu - $absorbed) . "\n\n";
$caption .= "## Sorotan\n";
foreach ($rising as $item) {
    $caption .= '- ' . $item['label'] . ' naik ' . $fmtPoints($item['delta']) . " poin\n";
}
$caption .= "\n## Alur unggahan\n"
    . "1. Slide 1: poster ini.\n"
    . "2. Slide 2: dokumen laporan DIPA terbaru (tautan Drive pada widget).\n"
    . "3. Slide 3: rincian delta per bulan bila diperlukan.\n\n"
    . "## Keterangan\n"
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

$motion = [
    'duration' => 8.0,
    'period' => $periodLabel,
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
.brand p { font-size: 25px; font-weight: 800; color: #2f140e; }
.brand small { display: block; margin-top: 3px; font-size: 16px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #8a7a6b; }
.kicker { margin-left: auto; padding: 9px 20px; border-radius: 999px; background: #8f1f0b; color: #fdf6e8; font-size: 15px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
h1 { font-family: 'Fraunces', Georgia, serif; font-size: 68px; font-weight: 700; line-height: 1.04; letter-spacing: -.02em; color: #2f140e; margin-top: 34px; }
h1 span { color: #8f1f0b; }
.lead { margin-top: 12px; font-size: 22px; font-weight: 600; color: #5a4a3d; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 30px; }
.card { background: #fff; border: 1px solid #e6dcc9; border-radius: 20px; padding: 22px 24px; box-shadow: 0 10px 24px rgba(47,20,14,.07); }
.card header { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.card-label { font-size: 16px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8a7a6b; }
.card-value { font-family: 'Fraunces', Georgia, serif; font-size: 62px; font-weight: 700; line-height: 1; color: #2f140e; margin-top: 12px; }
.meter { height: 10px; border-radius: 999px; background: #efe7d8; overflow: hidden; margin-top: 14px; }
.meter i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8f1f0b, #c0603f); }
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
    <div><p>Pengadilan Negeri Natana</p><small>Kelas II</small></div>
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
  el.innerHTML =
    '<header><p class="card-label">' + item.label + '</p>' +
    '<span class="delta delta--' + trend + '">' + (trend === 'up' ? '\u25B2 ' : '') + item.deltaText + '</span></header>' +
    '<p class="card-value" data-value></p>' +
    '<div class="meter"><i></i></div>' +
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
document.getElementById('lead').textContent = 'Penyerapan anggaran per ' + D.period + ', dibanding bulan sebelumnya.';
document.getElementById('panelTitle').textContent = 'Tren Penyerapan ' + months[0] + ' – ' + months[months.length - 1];
document.getElementById('footNote').textContent = 'Sumber: laporan realisasi anggaran DIPA ' + D.period;
document.querySelector('.brand p').textContent = 'Pengadilan Negeri Natuna';

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
    card.querySelector('.meter i').style.width = shown + '%';
    card.querySelector('.delta').style.opacity = String(span(t, 1.7 + i * 0.18, 0.35));
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
