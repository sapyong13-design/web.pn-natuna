<?php
/** Behavioral contract for SIPP schedule parsing and rendering. */
define('_JEXEC', true);
define('JPATH_ROOT', dirname(__DIR__));
require JPATH_ROOT . '/templates/pn_natuna_2026/sipp-schedule.php';

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$list = <<<'HTML'
<div id="pageTitle"><b>JADWAL SIDANG - Kamis, 23 Jul. 2026</b></div>
Pembaharuan Data : tanggal gagal dimuat, Total : 1 Perkara
<tr><td>1</td><td align="center">Kamis, 23 Jul. 2026</td><td>41/Pid.Sus/2026/PN Ntn</td><td>TIDAK</td><td>CAKRA</td><td>Dakwaan</td><td align="center"><a onClick="detilSidang('token')">Detil</a></td></tr>
HTML;
$schedule = pn_natuna_sipp_parse_schedule($list);
$expect($schedule['date_label'] === 'Kamis, 23 Jul. 2026', 'Date label must be parsed.');
$expect(($schedule['rows'][0]['no'] ?? '') === '1', 'Source row number must be parsed.');
$expect(($schedule['rows'][0]['case'] ?? '') === '41/Pid.Sus/2026/PN Ntn', 'Case number must be parsed.');

foreach ([
    'Terdakwa' => 'TRI YADI Bin ZULPADLI',
    'Pemohon' => 'SITI AMINAH',
    'Penggugat' => 'BUDI, dkk.',
    'Pihak' => 'ROSITA',
] as $label => $name) {
    $party = pn_natuna_sipp_parse_party("<tr><td id=\"first-child\">{$label} </td><td>{$name}<td></tr>");
    $expect($party === ['label' => $label, 'name' => $name], "{$label} must be parsed.");
}
$expect(pn_natuna_sipp_parse_party('') === ['label' => '', 'name' => ''], 'Failed detail fetch must return empty party fallback.');

$expect(pn_natuna_sipp_may_fetch_party(['case' => '41/Pid.Sus/2026/PN Ntn', 'detail' => 'https://example.test']) === true, 'Adult case may fetch public party detail.');
$expect(pn_natuna_sipp_may_fetch_party(['case' => '3/Pid.Sus-Anak/2026/PN Ntn', 'detail' => 'https://example.test']) === false, 'Juvenile case must never fetch party detail.');
$expect(pn_natuna_sipp_may_fetch_party(['case' => '4/Pdt.G/2026/PN Ntn', 'detail' => '']) === false, 'Missing detail URL must use party fallback.');

$cacheFile = pn_natuna_sipp_cache_file();
$original = is_file($cacheFile) ? file_get_contents($cacheFile) : null;
register_shutdown_function(static function () use ($cacheFile, $original): void {
    if ($original === null) {
        @unlink($cacheFile);
    } else {
        file_put_contents($cacheFile, $original);
    }
});

$fixture = [
    'days' => [
        'today' => ['date_label' => 'Kamis, 23 Jul. 2026', 'updated' => '', 'total' => '2', 'rows' => [
            ['case' => '41/Pid.Sus/2026/PN Ntn', 'party_label' => 'Terdakwa', 'party' => 'TRI YADI', 'room' => 'CAKRA', 'agenda' => 'Dakwaan', 'circuit' => 'TIDAK', 'detail' => 'https://example.test/detail/1'],
            ['case' => '3/Pid.Sus-Anak/2026/PN Ntn', 'party_label' => '', 'party' => '', 'room' => 'SARI', 'agenda' => 'Pembuktian', 'circuit' => 'TIDAK', 'detail' => 'https://example.test/detail/2'],
        ]],
        'tomorrow' => ['date_label' => 'Jumat, 24 Jul. 2026', 'updated' => '', 'total' => '0', 'rows' => []],
    ],
];
file_put_contents($cacheFile, json_encode($fixture));
ob_start();
pn_natuna_sipp_render_schedule();
$output = ob_get_clean();
$expect(str_contains($output, 'Jadwal Sidang Hari Ini &amp; Besok'), 'Renderer must announce both days.');
$expect(str_contains($output, '>01<') && str_contains($output, '>02<'), 'Cards must show numbered sequence.');
$expect(str_contains($output, 'Terdakwa</span>TRI YADI'), 'Card must show public primary party.');
$expect(substr_count($output, 'class="sipp-card-party"') === 1, 'Only eligible adult card may render party identity.');
$expect(str_contains($output, 'Tidak ada sidang besok'), 'Tomorrow empty state must remain visible in its panel.');
$expect(str_contains($output, 'role="tablist"') && str_contains($output, 'role="tabpanel"'), 'Day switcher must use accessible tab semantics.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "SIPP schedule contract: ok\n";
