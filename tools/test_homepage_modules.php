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

$result = $db->query("SELECT id, title, content, published, showtitle, position, module, access, language FROM {$config->dbprefix}modules WHERE id IN (482, 808, 816, 817)");
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
$expect(substr_count($modules[808]['content'] ?? '', 'class="maklumat-compact-doc"') === 2, 'Maklumat module must contain two documents.');
$expect(($modules[808]['showtitle'] ?? '0') === '1', 'Maklumat module must use its Joomla title.');
$expect(!str_contains($modules[808]['content'] ?? '', '<h2'), 'Maklumat content must not duplicate the Joomla module title.');
$expect(!str_contains($modules[808]['content'] ?? '', 'maklumat-compact-intro'), 'Maklumat content must not duplicate the section introduction.');
$expect(($modules[816]['published'] ?? '0') === '1', 'Kinerja module must be published.');
$expect(str_contains($modules[816]['content'] ?? '', 'survey-scores'), 'Kinerja module must contain survey scores.');
$expect(str_contains($modules[816]['content'] ?? '', 'SKM / IKM'), 'Kinerja module must contain SKM/IKM.');
$expect(str_contains($modules[816]['content'] ?? '', '>IPAK<'), 'Kinerja module must contain IPAK.');
$expect(str_contains($modules[816]['content'] ?? '', 'dipa-widget'), 'Kinerja module must contain DIPA realization.');
$expect(($modules[817]['published'] ?? '1') === '0', 'Legacy standalone DIPA module must remain unpublished.');
$expect(($modules[482]['published'] ?? '0') === '1', 'Role Model module must be published.');
$expect(str_contains($modules[482]['content'] ?? '', '/images/role-model/joko-ciptanto-role-model-2026.webp'), 'Wakil Ketua Role Model must use available WebP image.');
$expect(!str_contains($modules[482]['content'] ?? '', 'joko-ciptanto-role-model-2026.png'), 'Role Model module must not reference removed PNG image.');
$expect(is_file(__DIR__ . '/../images/role-model/joko-ciptanto-role-model-2026.webp'), 'Wakil Ketua Role Model image is missing.');
$expect(is_file(__DIR__ . '/../images/layanan/maklumat-pelayanan-2026.webp'), 'Maklumat Pelayanan image is missing.');
$expect(is_file(__DIR__ . '/../images/layanan/maklumat-layanan-informasi-publik.png'), 'Maklumat Informasi Publik image is missing.');
$expect(is_file(__DIR__ . '/../images/surveys/SKM_TW2_2026.png'), 'SKM publication image is missing.');
$expect(is_file(__DIR__ . '/../images/surveys/IPAK_TW2_2026.png'), 'IPAK publication image is missing.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "homepage module contract: ok\n";
