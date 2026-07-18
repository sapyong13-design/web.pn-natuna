<?php
/** Focused CSS contract for internal family navigation and accessibility. */
$css = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/css/template.css');
$article = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$profileMarkup = (string) file_get_contents(__DIR__ . '/profile-service-style.html');
$profileMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260715_profile_service_style.sql');
$jurisdictionMarkup = (string) file_get_contents(__DIR__ . '/jurisdiction-service-style.html');
$jurisdictionMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260715_jurisdiction_service_style.sql');
$headerBadgeMarkup = (string) file_get_contents(__DIR__ . '/header-brand-badges.html');
$headerBadgeMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260725_dark_header_brand_badges.sql');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };
$expect(str_contains($css, 'body.access-underline-links .main-menu a'), 'Main navigation needs a dedicated underline-mode override.');
$expect(str_contains($css, 'text-decoration: none !important'), 'Main navigation must disable per-line underline in accessibility mode.');
$expect(str_contains($css, '@media (min-width: 901px)') && str_contains($css, 'box-shadow: none !important'), 'Desktop underline mode must use block highlight without extra rules.');
$expect(str_contains($css, '@media (max-width: 900px)') && str_contains($css, 'box-shadow: inset 3px 0 var(--color-accent)'), 'Mobile drawer must retain one vertical accessibility indicator.');
$expect(str_contains($css, 'body.access-links-highlight .main-menu a:not(:focus-visible)'), 'Main navigation must suppress persistent link boxes while retaining keyboard focus.');
$expect(str_contains($css, 'border-bottom: 0 !important'), 'Underline mode must suppress competing item bottom borders.');
$expect(str_contains($css, '.main-menu #mod-menu1 > li > a:hover') && str_contains($css, 'text-decoration: none !important'), 'Desktop base, hover, and current states must never underline wrapped labels.');
$expect(str_contains($css, '@media (min-width: 901px) and (max-width: 1180px)') && str_contains($css, 'white-space: normal;'), 'Compact desktop menu labels must wrap without overflow.');
$expect((bool) preg_match('/\\.svc-subnav a \\{[^}]*min-height: 44px;/s', $css), 'Service-family submenu links need 44px touch targets.');
$expect(str_contains($article, 'class="svc-subnav"'), 'Profile detail pages must reuse service-family subnavigation.');
$expect(str_contains($article, 'aria-current="page"'), 'Profile detail navigation needs active state.');
$expect(str_contains($article, "str_starts_with(\$profilePath, '/profil-pengadilan/')"), 'Profile navigation must be scoped to profile detail routes.');
$expect(substr_count($profileMarkup, 'class="svc-card"') === 13, 'Profile landing must render 13 service cards.');
foreach (['svc-hero', 'svc-kicker', 'svc-lead', 'svc-grid', 'svc-icon', 'svc-more'] as $class) {
    $expect(str_contains($profileMarkup, 'class="' . $class), 'Profile landing missing exact service component ' . $class . '.');
}
$expect(!str_contains($profileMarkup, 'profile-gateway'), 'Profile landing must not retain old gateway markup.');
foreach (['Jejak lembaga', 'Arah lembaga', 'Pelajari mandat', 'Lihat bagan', 'Lihat wilayah', 'Lihat profil', 'Buka unit'] as $action) {
    $expect(str_contains($profileMarkup, $action), 'Profile cards need contextual action: ' . $action . '.');
}
$expect(substr_count($profileMarkup, '<svg viewBox=') >= 14, 'Profile cards need content-specific icons, not one repeated icon.');
$expect(str_contains($article, '$profileUnitPaths') && str_contains($article, '$showProfileUnits'), 'Profile unit submenu must appear only in the kepaniteraan branch.');
foreach (['svc-hero', 'svc-kicker', 'svc-lead', 'svc-grid', 'svc-card', 'svc-cta', 'svc-btn'] as $class) {
    $expect(str_contains($jurisdictionMarkup, 'class="' . $class), 'Jurisdiction page missing service component ' . $class . '.');
}
$expect(!str_contains($jurisdictionMarkup, 'profile-status'), 'Jurisdiction page must not retain old status-page components.');
$expect((bool) preg_match('/introtext=CONVERT\\(0x([0-9a-f]+) USING utf8mb4\\)/', $jurisdictionMigration, $jurisdictionHex), 'Jurisdiction migration must contain UTF-8 hex markup.');
if (!empty($jurisdictionHex[1])) {
    $expect(hex2bin($jurisdictionHex[1]) === rtrim($jurisdictionMarkup, "\r\n"), 'Jurisdiction migration payload must match readable source.');
}
$expect(!str_contains($jurisdictionMarkup, 'mencakup Kabupaten Natuna'), 'Jurisdiction copy must not assert an unverified single-regency scope.');
$expect(str_contains($jurisdictionMarkup, 'Kabupaten Natuna dan Kabupaten Kepulauan Anambas') && str_contains($jurisdictionMarkup, 'sedang diverifikasi'), 'Jurisdiction copy must clearly mark both regencies as under verification.');
$expect(strpos($headerBadgeMarkup, 'logo-ampuh-certified.png') < strpos($headerBadgeMarkup, 'logo-asn-berakhlak.png'), 'AMPUH badge must sit left of BerAKHLAK.');
$expect(str_contains($headerBadgeMarkup, 'class="ampuh-certified-mark"'), 'AMPUH badge needs its own size class.');
$expect(str_contains($headerBadgeMarkup, 'logo-asn-berakhlak-dark.png'), 'BerAKHLAK badge needs a dedicated dark-mode raster.');
$expect(str_contains($headerBadgeMarkup, 'asn-berakhlak-mark--light') && str_contains($headerBadgeMarkup, 'asn-berakhlak-mark--dark'), 'Header markup must render switchable BerAKHLAK variants.');
$expect(is_file(dirname(__DIR__) . '/images/brand/logo-asn-berakhlak-dark.png'), 'Dark BerAKHLAK raster must exist.');
$expect((bool) preg_match('/content=CONVERT\\(0x([0-9a-f]+) USING utf8mb4\\)/', $headerBadgeMigration, $headerBadgeHex), 'Header badge migration must contain UTF-8 hex markup.');
if (!empty($headerBadgeHex[1])) {
    $expect(hex2bin($headerBadgeHex[1]) === rtrim($headerBadgeMarkup, "\r\n"), 'Header badge migration payload must match readable source.');
}
$expect(str_contains($article, "'Visi & Misi'"), 'Profile submenu needs concise labels.');
$expect((bool) preg_match('/introtext=CONVERT\\(0x([0-9a-f]+) USING utf8mb4\\)/', $profileMigration, $profileHex), 'Profile migration must contain UTF-8 hex markup.');
$expect(str_contains($headerBadgeMarkup, 'header-brand-lockup'), 'AMPUH and BerAKHLAK must share one lockup container.');
$expect((bool) preg_match('/\\.header-brand-lockup \\{[^}]*display: flex;/s', $css), 'Blended header lockup must use one flex surface.');
$expect(str_contains($css, '.header-brand-lockup::after'), 'Blended header lockup needs a visual divider.');
$expect((bool) preg_match('/body\.is-dark \.court-brand-badges\.header-brand-lockup\s*\{[^}]*background:\s*#222d35/s', $css), 'Dark header badge plate must use a dark surface.');
$expect((bool) preg_match('/body\.is-dark \.asn-berakhlak-mark--light\s*\{[^}]*display:\s*none/s', $css), 'Dark mode must hide the light BerAKHLAK raster.');
$expect((bool) preg_match('/body\.is-dark \.asn-berakhlak-mark--dark\s*\{[^}]*display:\s*block/s', $css), 'Dark mode must reveal the dark BerAKHLAK raster.');
if (!empty($profileHex[1])) {
    $expect(hex2bin($profileHex[1]) === rtrim($profileMarkup, "\r\n"), 'Profile migration payload must exactly match readable profile markup source.');
}
$expect(str_contains($css, '@media (max-width: 620px)'), 'Family gateways need a mobile breakpoint.');
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "internal family consistency contract: ok\n";
