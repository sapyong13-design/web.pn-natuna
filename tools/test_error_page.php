<?php
/**
 * Kontrak halaman galat.
 *
 * Tautan berita pengadilan beredar lewat WhatsApp dan tetap diklik berbulan-bulan setelah
 * alamatnya berubah, jadi halaman 404 adalah halaman pertama yang dilihat sebagian warga.
 * Sebelum `error.php` ada, Joomla menyajikan bawaannya: berbahasa Inggris, tanpa lambang,
 * dan tanpa satu pun tautan keluar. Menghapus berkas ini mengembalikan keadaan itu diam-diam,
 * jadi keberadaannya dikunci di sini.
 */
$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$path = $root . '/templates/pn_natuna_2026/error.php';
$expect(is_file($path), 'The template must ship its own error page; Joomla\'s default is English and offers no way out.');

if (is_file($path)) {
    $source = (string) file_get_contents($path);
    // Komentar berkas ini mengutip teks Inggris lama sebagai catatan sejarah, jadi
    // pemeriksaan hanya boleh membaca kode dan keluarannya - bukan komentarnya.
    $emitted = preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $source);
    $css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template-4b123344.css');

    $expect(str_contains($emitted, 'Halaman tidak ditemukan'), 'The 404 message must be in Indonesian, the language of the people reading it.');
    $expect(!str_contains($emitted, 'You may not be able to visit'), 'Joomla\'s English boilerplate must not reappear.');
    // Tersesat tanpa jalan keluar adalah jalan buntu; halaman ini wajib menawarkan rute.
    $expect(substr_count($source, "['/") >= 4, 'The error page must offer real destinations, not just an apology.');
    $expect(str_contains($source, 'jadwal-sidang') && str_contains($source, 'layanan-ptsp'), 'The routes out must include what citizens actually come for: hearing schedules and PTSP.');
    $expect(str_contains($source, 'logo-pn-natuna.webp'), 'The court emblem must be present so the reader knows they are still on the right site.');
    $expect(str_contains($emitted, 'name="q"') && str_contains($emitted, '/cari'), 'The error page must let the reader search instead of guessing another URL, and must post to the live /cari route.');
    // Jejak tumpukan hanya boleh muncul saat debug menyala.
    $expect(str_contains($source, '$this->debug'), 'A stack trace may only render when debug is on.');
    $expect((bool) preg_match('/\.error-page__routes a \{[^}]*min-height: 44px/', $css), 'Error page routes must meet the 44px touch target.');
    $expect((bool) preg_match('/body\.is-dark \.error-page \{/', $css), 'The error page needs a dark mode like the rest of the site.');
    $expect(str_contains((string) file_get_contents($root . '/templates/pn_natuna_2026/templateDetails.xml'), '<filename>error.php</filename>'), 'error.php must be registered in templateDetails.xml or a template update will drop it.');
    $expect(str_contains($source, '/css/template-4b123344.css'), 'The error page must load the tracked canonical stylesheet on a clean deployment.');
    $expect(!str_contains($source, '/css/template.css'), 'The error page must not depend on an obsolete untracked stylesheet.');
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "error page contract: ok\n";
