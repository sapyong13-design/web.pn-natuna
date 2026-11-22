<?php
/** Local SVG atlas. The fee decree identifies listed places, not boundary polygons. */
defined('_JEXEC') or die;

$atlasDir = dirname(__DIR__, 3) . '/data/';
$maps = json_decode(file_get_contents($atlasDir . 'panjar-map.json'), true, 512, JSON_THROW_ON_ERROR);
$source = json_decode(file_get_contents($atlasDir . 'panjar-2026.json'), true, 512, JSON_THROW_ON_ERROR);
$overview = json_decode(file_get_contents($atlasDir . 'jurisdiction-overview.json'), true, 512, JSON_THROW_ON_ERROR);
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$root = \Joomla\CMS\Uri\Uri::root(true);
$regions = [
    'bunguran' => 'Bunguran',
    'pulautiga' => 'Pulau Tiga',
    'midai' => 'Midai & Pulau Laut',
    'subi' => 'Subi',
    'serasan' => 'Serasan',
    'anambas' => 'Kepulauan Anambas',
];
$summaries = [
    'bunguran' => 'Pulau Bunguran dan sekitar, mencakup tujuh kecamatan dalam lampiran. Kantor PN Natuna berada di Sungai Ulu, Bunguran Timur.',
    'pulautiga' => 'Gugus di barat daya Bunguran, mencakup Kecamatan Pulau Tiga dan Pulau Tiga Barat. Lokasi dalam lampiran antara lain Sabang Mawang Balai, Sededap, dan Pulau Tiga, dengan jarak tercatat 76–100 km.',
    'midai' => 'Dua gugus terpisah: Midai dan Suak Midai di selatan Bunguran, serta Pulau Laut di utara. Lokasi Pulau Laut tercantum di direktori, tetapi belum bertitik pada peta detail.',
    'subi' => 'Gugus Pulau Subi dan sekitarnya di tenggara Bunguran. Lampiran mencatat jarak 100,5 km untuk lokasi di Kecamatan Subi.',
    'serasan' => 'Gugus Serasan di tenggara Natuna, mencakup Kecamatan Serasan dan Serasan Timur. Lampiran mencatat jarak 156 km untuk lokasi di kedua kecamatan.',
    'anambas' => 'Gugus di barat daya Natuna, mencakup lokasi di Jemaja, Jemaja Timur, Palmatak, serta Siantan Kota, Selatan, Tengah, dan Timur.',
];
$maps = array_column($maps, null, 'id');
$districtRegions = ['Pulau Laut' => 'midai'];
$mapped = [];
foreach ($maps as $id => $map) {
    foreach ($map['points'] as $point) {
        $districtRegions[$point['k']] = $id;
        $mapped[$point['k']][$point['d']] = true;
    }
}
$districts = [];
foreach ($source['rows'] as $row) {
    $districts[$row['kec']][] = $row['desa'];
}
ksort($districts, SORT_NATURAL | SORT_FLAG_CASE);
$total = count($source['rows']);
$mappedCount = array_sum(array_map('count', $mapped));
?>
<article class="pn-atlas" aria-labelledby="atlas-title">
    <header class="atlas-intro">
        <div>
            <h1 id="atlas-title">Wilayah Yurisdiksi</h1>
            <p>Jelajahi desa dan kelurahan di Natuna dan Kepulauan Anambas yang tercantum dalam lampiran SK biaya pemanggilan PN Natuna.</p>
        </div>
        <a class="atlas-source" href="https://drive.google.com/file/d/1HPTP2qeKOH1Znp70XR4-bgBIc6_xcI8p/view"><span>Lampiran III · SK Ketua PN Natuna</span><strong><?= $escape($source['sk']['nomor']) ?></strong><span><?= $escape($source['sk']['tanggal']) ?> · Buka dokumen</span></a>
    </header>
    <div class="atlas-console">
        <section class="atlas-map" aria-label="Peta gugus pulau">
            <nav class="atlas-regions" aria-label="Pilih gugus pulau">
                <a href="#atlas-overview" data-atlas-region="overview">Semua Gugus</a>
                <?php foreach ($regions as $id => $label) : ?>
                    <a href="#atlas-<?= $escape($id) ?>" data-atlas-region="<?= $escape($id) ?>"><?= $escape($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <figure class="atlas-figure atlas-overview" id="atlas-overview" data-atlas-panel="overview">
                <figcaption><strong>Natuna &amp; Kepulauan Anambas</strong><p>Pilih penanda atau nama gugus untuk melihat pulau dan kecamatan yang tercantum dalam Lampiran III.</p></figcaption>
                <svg class="atlas-svg" viewBox="<?= $escape($overview['viewBox']) ?>" role="img" aria-label="Peta ringkasan Natuna dan Anambas dengan cincin jarak lurus dari kantor pengadilan">
                    <g class="atlas-land atlas-overview-land" fill-rule="evenodd">
                        <?php foreach ($overview['land'] as $path) : ?><path d="<?= $escape($path) ?>" vector-effect="non-scaling-stroke"/><?php endforeach; ?>
                    </g>
                    <?php [$cx, $cy] = $overview['court']; ?>
                    <g class="atlas-rings">
                        <?php foreach ($overview['rings'] as $km) :
                            // Stagger labels along each ring so they do not crowd the Bunguran markers.
                            $angle = deg2rad(match ($km) {50 => -64, 100 => -35, 200 => 155, default => 136});
                        ?>
                            <circle cx="<?= (float) $cx ?>" cy="<?= (float) $cy ?>" r="<?= (int) $km ?>" vector-effect="non-scaling-stroke"/>
                            <text x="<?= round($cx + cos($angle) * $km, 1) ?>" y="<?= round($cy + sin($angle) * $km, 1) ?>" text-anchor="middle" dominant-baseline="central"><?= (int) $km ?> km</text>
                        <?php endforeach; ?>
                    </g>
                    <g class="atlas-pin" transform="translate(<?= (float) $cx ?> <?= (float) $cy ?>)"><circle r="9"/><path d="M0 -5L5 0 0 5 -5 0Z"/><text x="14" y="4">PN Natuna</text></g>
                    <?php foreach ($overview['markers'] as $marker) : ?>
                        <g class="atlas-overview-marker" data-atlas-marker="<?= $escape($marker['region']) ?>" transform="translate(<?= (float) $marker['x'] ?> <?= (float) $marker['y'] ?>)">
                            <circle class="atlas-hit-area" r="14"/><circle r="5"/>
                            <text x="<?= (int) $marker['dx'] ?>" y="<?= (int) $marker['dy'] ?>" text-anchor="<?= $marker['dx'] < 0 ? 'end' : 'start' ?>"><?= $escape($marker['name']) ?></text>
                        </g>
                    <?php endforeach; ?>
                </svg>
                <p class="atlas-projection">Cincin 50 / 100 / 200 / 300 km · Jarak lurus dari kantor PN Natuna</p>
            </figure>
            <?php foreach ($regions as $id => $label) : $map = $maps[$id]; ?>
                <figure class="atlas-figure" id="atlas-<?= $escape($id) ?>" data-atlas-panel="<?= $escape($id) ?>">
                    <figcaption><strong><?= $escape($label) ?></strong><span><?= count($map['points']) ?> titik pada peta</span><p><?= $escape($summaries[$id]) ?></p></figcaption>
                    <svg class="atlas-svg" viewBox="0 0 <?= (int) $map['w'] ?> <?= (int) $map['h'] ?>" role="img" aria-label="Garis pantai dan titik desa untuk <?= $escape($label) ?>. Nama lokasi tersedia pada direktori.">
                        <defs><clipPath id="atlas-frame-<?= $escape($id) ?>"><rect width="<?= (int) $map['w'] ?>" height="<?= (int) $map['h'] ?>"/></clipPath></defs>
                        <g class="atlas-land" clip-path="url(#atlas-frame-<?= $escape($id) ?>)">
                            <?php foreach ($map['land'] as $path) : ?><path d="<?= $escape($path) ?>" vector-effect="non-scaling-stroke"/><?php endforeach; ?>
                        </g>
                        <g class="atlas-points">
                            <?php foreach ($map['points'] as $point) : ?>
                                <circle cx="<?= (float) $point['x'] ?>" cy="<?= (float) $point['y'] ?>" r="5" data-atlas-district="<?= $escape($point['k']) ?>" data-atlas-point="<?= $escape($point['d']) ?>" vector-effect="non-scaling-stroke"><title><?= $escape($point['d'] . ' · ' . $point['k']) ?></title></circle>
                            <?php endforeach; ?>
                        </g>
                        <?php if ($map['court']) : $pin = $map['court']; ?>
                            <g class="atlas-pin" transform="translate(<?= (float) $pin['x'] ?> <?= (float) $pin['y'] ?>)"><circle r="12"/><path d="M0 -7L7 0 0 7 -7 0Z"/><title>Kantor PN Natuna, Sungai Ulu</title></g>
                        <?php endif; ?>
                    </svg>
                    <span class="atlas-north" aria-hidden="true">U<svg viewBox="0 0 16 25" width="16" height="25" fill="none" stroke="currentColor"><path d="M8 2 2 15l6-3 6 3L8 2Zm0 10v11"/></svg></span>
                </figure>
            <?php endforeach; ?>
            <p class="atlas-map-label" data-atlas-map-label aria-live="polite" hidden></p>
            <div class="atlas-key"><span><i class="atlas-key-land"></i> Daratan</span><span><i class="atlas-key-marker"></i> Lokasi</span><span><i class="atlas-key-office"></i> Kantor PN Natuna</span><span class="atlas-credits"><a href="https://www.naturalearthdata.com/">Natural Earth</a><a href="https://www.openstreetmap.org/copyright">© OpenStreetMap</a></span></div>
        </section>
        <section class="atlas-directory" aria-labelledby="atlas-directory-title">
            <header>
                <h2 id="atlas-directory-title">Direktori wilayah</h2>
                <p><?= count($districts) ?> kecamatan · <?= $total ?> desa/kelurahan dalam Lampiran III</p>
            </header>
            <div class="atlas-controls" hidden>
                <label for="atlas-district">Kecamatan</label>
                <select id="atlas-district" data-atlas-select>
                    <option value="">Semua kecamatan</option>
                    <?php foreach ($districts as $district => $villages) : ?>
                        <option value="<?= $escape($district) ?>" data-region="<?= $escape($districtRegions[$district]) ?>"><?= $escape($district) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="atlas-selection" data-atlas-selection aria-live="polite"></p>
            </div>
            <div class="atlas-locations">
                <?php foreach ($districts as $district => $villages) : ?>
                    <details data-atlas-group="<?= $escape($district) ?>" data-region="<?= $escape($districtRegions[$district]) ?>">
                        <summary><strong><?= $escape($district) ?></strong><span><?= count($villages) ?></span></summary>
                        <ul>
                            <?php foreach ($villages as $village) : ?>
                                <li data-atlas-village="<?= $escape($village) ?>" data-mapped="<?= isset($mapped[$district][$village]) ? 'yes' : 'no' ?>"><span><?= $escape($village) ?></span><?php if (!isset($mapped[$district][$village])) : ?><small>Belum dipetakan</small><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <p class="atlas-note"><strong>Peta referensi, bukan peta batas hukum.</strong> Cincin pada peta ringkasan menunjukkan jarak lurus, bukan rute perjalanan, zona tarif, atau batas yurisdiksi. Keterangan jarak pada panel detail mengikuti Lampiran III. <?= $total - $mappedCount ?> lokasi dalam lampiran belum bertitik pada peta detail. Untuk memastikan kewenangan atas perkara tertentu, hubungi PTSP.</p>
    <div class="atlas-contact"><div><strong>Pengadilan Negeri Natuna Kelas II</strong><span>Jalan Batu Sisir, Desa Sungai Ulu · Bunguran Timur</span></div><a href="<?= $escape($root) ?>/kontak">Hubungi PTSP <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6"/></svg></a></div>
</article>
