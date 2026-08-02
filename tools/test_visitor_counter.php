<?php
/**
 * Kontrak penghitung kunjungan.
 *
 * Angka publik di situs ini wajib bisa ditelusuri ke klaimnya. Penghitung ini menghitung
 * permintaan halaman, bukan orang - satu pembaca yang membuka lima halaman terhitung
 * lima - dan tidak mengumpulkan identitas apa pun, jadi ia tidak akan pernah bisa
 * menyebut "pengunjung". Tiga hal yang dijaga: labelnya jujur, mesin tidak ikut dihitung,
 * dan tabelnya dirujuk lewat prefiks Joomla bukan `pnn_` yang dipatok mati.
 */
require_once __DIR__ . '/../configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$counter = (string) file_get_contents($root . '/templates/pn_natuna_2026/stats-counter.php');
$index = (string) file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');

// Label. "Pengunjung" adalah klaim yang datanya tidak sanggup dukung.
$expect(str_contains($counter, 'Kunjungan halaman'), 'The counter must be labelled as page visits.');
$expect(!preg_match('/>\s*(Jumlah )?Pengunjung\s*</i', $counter), 'The counter must never claim to count people; it counts page requests.');
$expect(str_contains($counter, 'Dihitung sejak'), 'A total without a start date cannot be traced to what it claims.');
$expect(str_contains($counter, 'bukan jumlah orang'), 'The note must state plainly what the number is not.');

// Lalu lintas mesin. Tanpa penyaring, satu perayap bisa melipatgandakan angka publik.
$expect(str_contains($counter, 'pn_natuna_visitor_is_machine'), 'Machine traffic must be filtered before it reaches the public counter.');
foreach (['bot', 'crawl', 'spider', 'headless', 'curl/', 'python-requests'] as $token) {
    $expect(str_contains($counter, $token), "The machine filter must recognise \"{$token}\".");
}
$expect((bool) preg_match('/function pn_natuna_track_visitor[^{]*\{\s*if \(pn_natuna_visitor_is_machine\(\)\)/s', $counter), 'The filter must run before any write, not after.');

// Prefiks tabel. `pnn_` yang dipatok mati akan gagal diam-diam saat cutover.
$expect(!preg_match('/\bpnn_visitor_/', $counter), 'Tables must be referenced through the Joomla prefix, not a hardcoded pnn_.');
$expect(substr_count($counter, '#__visitor_') >= 2, 'The counter must use the #__ prefix placeholder.');

// Tempat tampilnya: kaki situs, bukan rel beranda.
$expect(str_contains($index, 'pn_natuna_render_visitor_stats'), 'The footer must render the counter.');
$posisi = strpos($index, 'pn_natuna_render_visitor_stats');
$kakiMulai = strpos($index, '<footer class="site-footer">');
$kakiSelesai = strpos($index, '</footer>', $kakiMulai ?: 0);
$expect($kakiMulai !== false && $posisi > $kakiMulai && $posisi < $kakiSelesai, 'The counter belongs in the footer, where provenance lives - not in the homepage service rail.');

// Pita melintang penuh, dan catatan kecilnya tidak boleh pas di ambang kontras.
$expect((bool) preg_match('/\.footer-stats \{[^}]*grid-column: 1 \/ -1/', $css), 'The strip must span the footer grid, otherwise its rule stops mid-width.');
$expect(
    (bool) preg_match('/\.footer-stats__note \{[^}]*rgba\(255, 255, 255, 0\.(5[1-9]|[6-9]\d)\)/', $css),
    'The note sits at 12px on a dark ground; alpha .45 lands exactly on 4.50:1 with no margin at all.'
);

// Kolom tanggal mulai wajib ada, kalau tidak labelnya diam-diam hilang.
$kolom = $db->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$config->dbprefix}visitor_totals' AND COLUMN_NAME = 'counting_since'");
$expect($kolom && (int) $kolom->fetch_assoc()['c'] === 1, 'Column counting_since is missing; run migration 20260907.');

$row = $db->query("SELECT total_hits, counting_since FROM {$config->dbprefix}visitor_totals WHERE counter_id = 1");
$data = $row ? $row->fetch_assoc() : null;
$expect($data !== null, 'The counter row is missing.');
$expect($data !== null && $data['counting_since'] !== null, 'counting_since must be populated, or the footer drops the start date silently.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'visitor counter contract: ok (total ' . number_format((int) ($data['total_hits'] ?? 0)) . " page visits since {$data['counting_since']})\n";
