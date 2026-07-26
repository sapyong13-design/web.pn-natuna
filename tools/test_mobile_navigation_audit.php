<?php
/** Source contract for audited mobile navigation behavior. */
$root = dirname(__DIR__);
$index = (string) file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$js = (string) file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };

$expect(str_contains($index, 'href="/layanan-publik/regulasi-pengaduan"'), 'Drawer complaint shortcut must use canonical route.');
$expect(str_contains($index, 'class="dark-status-off">Mati<'), 'Dark mode off copy must be Mati.');
$expect(str_contains($index, 'class="dark-status-on">Aktif<'), 'Dark mode on copy must be Aktif.');
$expect(str_contains($js, 'mobile-menu-summary-link'), 'Runtime parent summary link is missing.');
$expect(str_contains($js, 'scrollActiveMenuItem'), 'Active menu scroll helper is missing.');
$expect(str_contains($js, "setAttribute('aria-current', 'page')"), 'Active link must receive aria-current page.');
$expect(str_contains($css, '--nav-drawer-bg:'), 'Drawer color tokens are missing.');
$expect((bool) preg_match('/\.main-menu-list \.dark-status-on\s*\{[^}]*display:\s*none;/s', $css), 'Dark on status needs a selector stronger than generic menu span rules.');
$expect((bool) preg_match('/\.main-menu-list \.dark-toggle\[aria-pressed="true"\] \.dark-status-off\s*\{[^}]*display:\s*none;/s', $css), 'Pressed dark toggle must hide off status.');
$expect(str_contains($js, 'offStatus.hidden = active'), 'Dark toggle must hide off status through the hidden attribute.');
$expect(str_contains($js, 'onStatus.hidden = !active'), 'Dark toggle must hide on status through the hidden attribute.');
// Lantai keterbacaan menu level tiga. Dulu .78rem literal (12,48px). Skala kecil
// --step--2 menghitung ke 12,16px, di bawah lantai itu, jadi level ini wajib
// memakai --step--1 (14,08px) - bukan langkah yang lebih kecil.
$expect((bool) preg_match('/\.main-menu li ul li ul a\s*\{[^}]*font-size:\s*var\(--step--1\)/s', $css), 'Third-level links must not fall below the recorded readability floor.');
$expect((bool) preg_match('/\.mobile-menu-footer\s*\{[^}]*min-height:\s*68px;/s', $css), 'Drawer footer must use compact 68px minimum height.');
$expect(str_contains($css, '.mobile-menu-summary-link'), 'Summary link styling is missing.');
$expect((bool) preg_match('/@media \(min-width:\s*761px\).*?\.main-menu > \.main-menu-list \.mobile-menu-scroll > ul > li > ul > li > a\s*\{[^}]*justify-content:\s*flex-start;[^}]*text-align:\s*left/s', $css), 'All desktop submenu links must align left.');
$expect((bool) preg_match('/@media \(min-width:\s*761px\).*?\.main-menu > \.main-menu-list \.mobile-menu-scroll > ul > li > ul > \.mobile-menu-group-label\s*\{[^}]*color:\s*#[0-9a-fA-F]{6};[^}]*text-align:\s*left;[^}]*text-transform:\s*uppercase/s', $css), 'Desktop submenu group labels need readable light hierarchy.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?\.main-menu li ul a\s*\{[^}]*justify-content:\s*flex-start;[^}]*text-align:\s*left/s', $css), 'Mobile submenu links must align left.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?\.mobile-menu-group-label\s*\{[^}]*color:\s*var\(--nav-drawer-accent\);[^}]*text-align:\s*left/s', $css), 'Mobile menu group labels need explicit readable color and left alignment.');

if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "mobile navigation audit contract: ok\n";
