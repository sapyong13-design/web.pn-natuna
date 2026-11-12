<?php
/** Focused integration check for restored homepage modules. */
require_once __DIR__ . '/../configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$result = $db->query("SELECT id, title, content, published, showtitle, position, module, access, language FROM {$config->dbprefix}modules WHERE id IN (480, 482, 808, 816, 817)");
if (!$result) {
    fwrite(STDERR, "Module query failed: {$db->error}\n");
    exit(1);
}

$modules = [];
while ($row = $result->fetch_assoc()) {
    $modules[(int) $row['id']] = $row;
}

$menuResult = $db->query("SELECT moduleid, menuid FROM {$config->dbprefix}modules_menu WHERE moduleid IN (482, 808, 816, 817)");
if (!$menuResult) {
    fwrite(STDERR, "Module menu query failed: {$db->error}\n");
    exit(1);
}
$moduleMenus = [];
while ($row = $menuResult->fetch_assoc()) {
    $moduleMenus[(int) $row['moduleid']][] = (int) $row['menuid'];
}
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$expect(isset($modules[480]), 'Module 480 is missing.');
$expect(isset($modules[482]), 'Module 482 is missing.');
$expect(isset($modules[808]), 'Module 808 is missing.');
$expect(isset($modules[816]), 'Module 816 is missing.');
$expect(isset($modules[817]), 'Module 817 is missing.');
$expectedPositions = [482 => 'home-role-model', 808 => 'home-alerts', 816 => 'home-survey', 817 => 'home-dipa'];
foreach ($expectedPositions as $id => $position) {
    $expect(($modules[$id]['position'] ?? '') === $position, "Module {$id} must use position {$position}.");
    $expect(($modules[$id]['module'] ?? '') === 'mod_custom', "Module {$id} must be a custom module.");
    $expect(($modules[$id]['access'] ?? '0') === '1', "Module {$id} must be public.");
    $expect(($modules[$id]['language'] ?? '') === '*', "Module {$id} must be available in all languages.");
    $expect(($moduleMenus[$id] ?? []) === [0], "Module {$id} must be assigned globally exactly once.");
}
$expect(($modules[808]['published'] ?? '0') === '1', 'Maklumat module must be published.');
$expect(str_contains($modules[808]['content'] ?? '', 'maklumat-compact-docs'), 'Maklumat module must render compact document cards.');
$expect(substr_count($modules[808]['content'] ?? '', 'class="maklumat-compact-doc') >= 2, 'Maklumat module must contain at least two documents.');
$expect(($modules[808]['showtitle'] ?? '1') === '0', 'Maklumat module must hide its redundant Joomla title.');
$expect(!str_contains($modules[808]['content'] ?? '', '<h2'), 'Maklumat content must not duplicate the Joomla module title.');
$expect(!str_contains($modules[808]['content'] ?? '', 'maklumat-compact-intro'), 'Maklumat content must not duplicate the section introduction.');
$expect(($modules[816]['published'] ?? '0') === '1', 'Kinerja module must be published.');
$kinerja = $modules[816]['content'] ?? '';
$expect(str_contains($kinerja, 'survey-scores'), 'Kinerja module must contain survey scores.');
// Kartu kini menawarkan pilihan triwulan seperti widget DIPA, jadi setiap periode
// membawa satu tile SKM dan satu tile IPAK. Yang dijaga: labelnya tepat "SKM" (bukan
// "SKM / IKM") dan jumlah tile seimbang dengan jumlah panel yang dirender.
// `class="survey-panel` juga cocok dengan pembungkus `survey-panels`; batasi ke
// elemen panel supaya hitungannya sama dengan jumlah tile per periode.
preg_match_all('~class="survey-panel[ "]~', $kinerja, $panelMatches);
$panels = count($panelMatches[0]);
$expect($panels >= 1, 'Kinerja module must render at least one survey period panel.');
$expect(substr_count($kinerja, '<span class="survey-score-label">SKM</span>') === $panels,
    'Every survey period must label its tile "SKM" (not "SKM / IKM").');
$expect(substr_count($kinerja, '<span class="survey-score-label">IPAK</span>') === $panels,
    'Every survey period must contain an IPAK tile.');
$expect(str_contains($kinerja, 'data-survey-board'), 'Survey card must be wrapped for the period picker.');
$expect($panels > 1 ? str_contains($kinerja, 'data-survey-tab') : true,
    'Multiple survey periods must expose a period picker.');
// Kinerja (skor survei) dan Akuntabilitas (realisasi DIPA) sengaja dipisah jadi dua
// kartu: digabung, kartu sidebar jadi sangat tinggi dan pembaca harus menggulir di
// dalam kartu untuk melihat blok kedua.
$expect(!str_contains($kinerja, 'dipa-widget'), 'Kinerja module must not carry the DIPA widget.');
$expect(($modules[817]['published'] ?? '0') === '1', 'Akuntabilitas module must be published.');
$akuntabilitas = $modules[817]['content'] ?? '';
$expect(str_contains($akuntabilitas, 'dipa-widget'), 'Akuntabilitas module must contain DIPA realization.');
$expect(str_contains($akuntabilitas, 'data-dipa-board'), 'Akuntabilitas widget must expose the period picker hook.');
$expect(!str_contains($akuntabilitas, 'survey-scores'), 'Akuntabilitas module must not carry survey tiles.');
$expect(($modules[482]['published'] ?? '0') === '1', 'Role Model module must be published.');
$expect(str_contains($modules[482]['content'] ?? '', '/images/role-model/joko-ciptanto-role-model-2026.webp'), 'Wakil Ketua Role Model must use available WebP image.');
$expect(!str_contains($modules[482]['content'] ?? '', 'joko-ciptanto-role-model-2026.png'), 'Role Model module must not reference removed PNG image.');
$expect(substr_count($modules[482]['content'] ?? '', 'loading="lazy"') === 2, 'Both below-fold Role Model portraits must be lazy loaded.');
$expect(substr_count($modules[482]['content'] ?? '', 'decoding="async"') === 2, 'Both Role Model portraits must decode asynchronously.');
$facilityContent = $modules[480]['content'] ?? '';
$expect(($modules[480]['published'] ?? '0') === '1', 'Facility gallery module must be published.');
$expect(substr_count($facilityContent, 'loading="lazy"') === 4, 'All facility gallery photos must be lazy loaded.');
$expect(substr_count($facilityContent, 'decoding="async"') === 4, 'All facility gallery photos must decode asynchronously.');
$expect(substr_count($facilityContent, 'srcset="') === 4, 'All facility gallery photos must expose responsive sources.');
$expect(substr_count($facilityContent, 'sizes="(max-width: 760px) calc(100vw - 40px), 280px"') === 4, 'Facility gallery responsive sizes must match the card layout.');
$facilityVariants = [
    'akses-disabilitas-2026-400.webp',
    'akses-disabilitas-2026-800.webp',
    'lokasi-kantor-2026-400.webp',
    'lokasi-kantor-2026-800.webp',
    'posbakum-2026-400.webp',
    'posbakum-2026-800.webp',
    'ruang-ptsp-2026-400.webp',
    'ruang-ptsp-2026-800.webp',
    'ruang-ptsp-2026-1200.webp',
];
foreach ($facilityVariants as $variant) {
    $expect(is_file(__DIR__ . '/../images/layanan/gallery/' . $variant), "Facility gallery variant {$variant} is missing.");
}
$expect(is_file(__DIR__ . '/../images/role-model/joko-ciptanto-role-model-2026.webp'), 'Wakil Ketua Role Model image is missing.');
$expect(is_file(__DIR__ . '/../images/layanan/maklumat-pelayanan-2026.webp'), 'Maklumat Pelayanan image is missing.');
$expect(is_file(__DIR__ . '/../images/layanan/maklumat-layanan-informasi-publik.webp'), 'Maklumat Informasi Publik image is missing.');
// Setiap periode yang ditawarkan harus punya dokumen dan pratinjaunya, karena tile
// menyimpan jalur dokumen di `data-maklumat-zoom`.
preg_match_all('~data-maklumat-zoom="([^"]+)"~', $kinerja, $zoomMatches);
$expect(count($zoomMatches[1]) === $panels * 2, 'Each period must expose exactly two publication documents.');
foreach ($zoomMatches[1] as $document) {
    $path = __DIR__ . '/..' . html_entity_decode($document, ENT_QUOTES, 'UTF-8');
    $expect(is_file($path), "Survey publication image is missing: {$document}");
    $preview = preg_replace('~\.(png|jpe?g)$~i', '-preview.webp', $path);
    $expect(is_file($preview), "Survey publication preview is missing: {$preview}");
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "homepage module contract: ok\n";
