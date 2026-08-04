<?php
/** Focused CSS contract for internal family navigation and accessibility. */
$css = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/css/template.css');
$article = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$profileMarkup = (string) file_get_contents(__DIR__ . '/profile-service-style.html');
$profileMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260814_clarify_secretariat_subdivision_cards.sql');
$jurisdictionMarkup = (string) file_get_contents(__DIR__ . '/jurisdiction-service-style.html');
$jurisdictionMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260715_jurisdiction_service_style.sql');
$headerBadgeMarkup = (string) file_get_contents(__DIR__ . '/header-brand-badges.html');
$brandAssetMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260824_optimize_brand_logo_assets.sql');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };
$expect(str_contains($css, 'body.access-underline-links .main-menu a'), 'Main navigation needs a dedicated underline-mode override.');
$expect((bool) preg_match('/body\.access-underline-links \.main-menu a\s*\{[^}]*text-decoration:\s*none !important;/s', $css), 'Main navigation must disable per-line underline in accessibility mode.');
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
$expect(substr_count($profileMarkup, 'class="svc-card"') === 17, 'Profile landing must render all 17 current profile routes.');
foreach (['svc-hero', 'svc-kicker', 'svc-lead', 'svc-grid', 'svc-icon', 'svc-more'] as $class) {
    $expect(str_contains($profileMarkup, 'class="' . $class), 'Profile landing missing exact service component ' . $class . '.');
}
$expect(!str_contains($profileMarkup, 'profile-gateway'), 'Profile landing must not retain old gateway markup.');
foreach (['Jejak lembaga', 'Arah lembaga', 'Pelajari mandat', 'Lihat bagan', 'Lihat wilayah', 'Lihat profil', 'Buka unit'] as $action) {
    $expect(str_contains($profileMarkup, $action), 'Profile cards need contextual action: ' . $action . '.');
}
foreach (['kata-sambutan', 'subbagian-kepegawaian-ortala', 'subbagian-ptip', 'subbagian-umum-keuangan'] as $latestProfileRoute) {
    $expect(str_contains($profileMarkup, $latestProfileRoute), 'Profile landing missing latest route: ' . $latestProfileRoute . '.');
}
$expect(substr_count($profileMarkup, 'class="svc-directory-group"') === 2, 'Profile landing must separate level-3 routes into two hierarchy groups.');
$expect(str_contains($profileMarkup, 'Unit Kepaniteraan') && str_contains($profileMarkup, 'Subbagian Kesekretariatan'), 'Profile landing needs explicit parent-unit headings.');
$expect(substr_count($profileMarkup, '<svg viewBox=') >= 14, 'Profile cards need content-specific icons, not one repeated icon.');
$expect(str_contains($profileMigration, "path='profil-pengadilan'") && str_contains($profileMigration, 'WHERE id=@profile_article_id'), 'Profile migration must update the article linked by the menu, not a duplicate alias.');
$expect(str_contains($article, '$profileRegistryPaths') && str_contains($article, '$profileSecretariatPaths') && str_contains($article, '$showProfileUnits'), 'Profile unit submenu must follow kepaniteraan and kesekretariatan branches.');
foreach (['subbagian-ptip', 'subbagian-kepegawaian-ortala', 'subbagian-umum-keuangan'] as $secretariatRoute) {
    $expect(str_contains($article, $secretariatRoute), 'Secretariat submenu route missing: ' . $secretariatRoute . '.');
}
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
$expect(strpos($headerBadgeMarkup, 'logo-ampuh-certified.webp') < strpos($headerBadgeMarkup, 'logo-asn-berakhlak.webp'), 'AMPUH badge must sit left of BerAKHLAK.');
$expect(str_contains($headerBadgeMarkup, 'class="ampuh-certified-mark"'), 'AMPUH badge needs its own size class.');
// Migrasi 20260725 menyimpan snapshot PNG historis. Payload DB aktif adalah
// hasil tepat snapshot itu setelah semua substitusi asset migrasi 20260824.
// Jadi fixture tetap byte-identik dengan database, namun tidak mengabadikan PNG.
$expect(str_contains($headerBadgeMarkup, 'logo-asn-berakhlak-dark.webp'), 'BerAKHLAK badge needs the current dedicated dark-mode WebP raster.');
$expect(str_contains($headerBadgeMarkup, 'asn-berakhlak-mark--light') && str_contains($headerBadgeMarkup, 'asn-berakhlak-mark--dark'), 'Header markup must render switchable BerAKHLAK variants.');
$expect(is_file(dirname(__DIR__) . '/images/brand/logo-asn-berakhlak-dark.webp'), 'Current dark BerAKHLAK WebP raster must exist.');
$expect(str_contains($brandAssetMigration, "'logo-asn-berakhlak-dark.png', 'logo-asn-berakhlak-dark.webp'"), 'Brand asset migration must promote the dark badge from PNG to WebP.');
$expect(!str_contains($headerBadgeMarkup, '.png'), 'Header fixture must not retain retired PNG badge assets.');
$headerBadgeMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260725_dark_header_brand_badges.sql');
$expect((bool) preg_match('/content=CONVERT\\(0x([0-9a-f]+) USING utf8mb4\\)/', $headerBadgeMigration, $headerBadgeHex), 'Header badge baseline migration must contain UTF-8 hex markup.');
$expectedHeaderBadgeMarkup = !empty($headerBadgeHex[1]) ? str_replace(['logo-pn-natuna.png', 'logo-ampuh-certified.png', 'logo-asn-berakhlak-dark.png', 'logo-asn-berakhlak.png'], ['logo-pn-natuna.webp', 'logo-ampuh-certified.webp', 'logo-asn-berakhlak-dark.webp', 'logo-asn-berakhlak.webp'], hex2bin($headerBadgeHex[1])) : '';
$expect($expectedHeaderBadgeMarkup === rtrim($headerBadgeMarkup, "\r\n"), 'Header fixture must exactly match the baseline payload after the current brand-asset migration.');
$expect(str_contains($article, "'Visi & Misi'"), 'Profile submenu needs concise labels.');
$expect((bool) preg_match('/introtext=CONVERT\\(0x([0-9a-f]+) USING utf8mb4\\)/', $profileMigration, $profileHex), 'Profile migration must contain UTF-8 hex markup.');
$expect(str_contains($headerBadgeMarkup, 'header-brand-lockup'), 'AMPUH and BerAKHLAK must share one lockup container.');
$expect((bool) preg_match('/\\.header-brand-lockup \\{[^}]*display: flex;/s', $css), 'Blended header lockup must use one flex surface.');
// Pemisah dulu pseudo-element absolut ber-`left` hardcoded, dan asersi ini
// mengunci mekanismenya. Posisi itu dihitung untuk logo AMPUH 62-80px lalu
// meleset ke tengah wordmark BerAKHLAK setelah override >=1024 mengecilkan logo
// jadi 46-58px. Yang wajib dijaga adalah ADANYA pemisah di antara kedua badge,
// bukan cara menggambarnya; implementasi sekarang memakai border pada badge
// kedua sehingga posisinya mengikuti ukuran tetangganya.
$expect((bool) preg_match('/\.header-brand-lockup(::after|[^{]*\.asn-berakhlak-link)\s*\{[^}]*border-left:\s*1px solid|\.header-brand-lockup::after/s', $css), 'Blended header lockup needs a visual divider between both badges.');
$expect((bool) preg_match('/body\.is-dark \.court-brand-badges\.header-brand-lockup\s*\{[^}]*background:\s*#222d35/s', $css), 'Dark header badge plate must use a dark surface.');
$expect((bool) preg_match('/body\.is-dark \.asn-berakhlak-mark--light\s*\{[^}]*display:\s*none/s', $css), 'Dark mode must hide the light BerAKHLAK raster.');
$expect((bool) preg_match('/body\.is-dark \.asn-berakhlak-mark--dark\s*\{[^}]*display:\s*block/s', $css), 'Dark mode must reveal the dark BerAKHLAK raster.');
if (!empty($profileHex[1])) {
    $expect(hex2bin($profileHex[1]) === rtrim($profileMarkup, "\r\n"), 'Profile migration payload must exactly match readable profile markup source.');
}
$expect((bool) preg_match('/body\.is-dark \.content-primary \.svc-cta a\.svc-btn-gold[\s\S]*?color:\s*#2f140e;/s', $css), 'Dark CTA gold buttons must retain dark high-contrast text.');
$expect((bool) preg_match('/body\.is-dark \.content-primary \.svc-cta a\.svc-btn-line[\s\S]*?color:\s*#fff8ed;/s', $css), 'Dark CTA outline buttons must retain light high-contrast text.');
$expect((bool) preg_match('/body\.is-dark \.content-primary \.svc-cta :is\(h2, p\)[\s\S]*?color:\s*#fff8ed;/s', $css), 'Dark CTA heading and copy must stay light over the dark gradient.');
$brandOverride = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/mod_custom/default.php');
$expect(str_contains($brandOverride, "\$tag = \$active && \$active->home ? 'h1' : 'p';"), 'Brand module must keep h1 only on the home menu item.');
$expect(str_contains($brandOverride, 'class="brand-title"'), 'Brand module needs a stable class independent of heading tag.');
$expect(str_contains($css, '.brand-lockup .brand-title'), 'Brand title styling must not depend on an h1 element.');
$expect((bool) preg_match('/\.news-portal__news-card > a\s*\{[^}]*min-height:\s*179px;/s', $css), 'Mobile news cards need a consistent minimum height.');
$expect(str_contains($css, '.brand-lockup p:not(.brand-title)'), 'Mobile header must hide the address without hiding contextual brand title paragraphs.');
$spotlightMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260819_redesign_change_agent_role_model_spotlights.sql');
$expect(substr_count($spotlightMigration, 'class="zi-spotlight ') === 2, 'Agen Perubahan and Role Model must each receive one editorial spotlight.');
$expect(str_contains($spotlightMigration, 'zi-spotlight__mark') && !str_contains($spotlightMigration, 'zi-spotlight__year'), 'Spotlight decoration must not hardcode a stale year.');
$expect(str_contains($css, '.zi-spotlight--agent') && str_contains($css, '.zi-spotlight--role'), 'Both Zona Integritas spotlights need distinct visual themes.');
$expect((bool) preg_match('/body\.is-dark \.content-primary \.zi-spotlight__copy > a[\s\S]*?color:\s*#fff;/s', $css), 'Dark spotlight buttons must retain white high-contrast text.');
$transparencyArrowMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260820_normalize_transparency_document_arrows.sql');
$expect(str_contains($transparencyArrowMigration, 'REGEXP_REPLACE') && str_contains($transparencyArrowMigration, 'aria-hidden="true"'), 'Transparency migration must remove every legacy markup arrow variant.');
$expect((bool) preg_match('/\.transparency-archive\s*\{[^}]*width:\s*min\(100%,\s*1040px\);/s', $css), 'Two-column transparency archives need a bounded desktop width.');
$expect((bool) preg_match('/a\.transparency-document::after\s*\{[^}]*content:\s*"↗";/s', $css), 'Transparency documents must use exactly one CSS-generated external arrow.');
$transparencyRepairMigration = (string) file_get_contents(__DIR__ . '/../database/migrations/20260821_repair_transparency_document_links.sql');
$templateJs = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/js/template.js');
$expect(str_contains($transparencyRepairMigration, "REPLACE(introtext,'</span>1','</span></a>')"), 'Transparency repair must restore closing anchors, not merely hide the visible artifact.');
$expect((bool) preg_match('/\.transparency-archive\s*\{[^}]*grid-template-columns:\s*repeat\(2,/s', $css), 'Transparency archives need two desktop columns.');
$expect(str_contains($templateJs, 'const initialLimit = 10;') && str_contains($templateJs, 'Tampilkan ${hiddenDocuments.length} dokumen lainnya'), 'Long transparency archives need an accessible ten-document disclosure.');
$expect(str_contains($templateJs, 'transparency-document__icon') && str_contains($templateJs, 'transparency-document__year'), 'Transparency cards need document icons and safe year badges.');
$expect(str_contains($templateJs, "meta.innerHTML = '<span>Dokumen resmi</span><b>Google Drive</b>'"), 'Repeated Google Drive instructions must become compact metadata badges.');
$expect(!str_contains($templateJs, 'transparency-archive__source'), 'Transparency archive must not repeat source instructions above every grid.');
$expect(str_contains($templateJs, "documentLink.setAttribute('aria-label'"), 'External document behavior needs a complete accessible label.');
$expect(str_contains($css, '[data-document-family="performance"]') && str_contains($css, '[data-document-family="finance"]') && str_contains($css, '[data-document-family="survey"]') && str_contains($css, '[data-document-family="information"]'), 'Transparency document families need distinct accents.');
$expect((bool) preg_match('/a\.transparency-document\s*\{[^}]*min-height:\s*105px;/s', $css), 'Desktop transparency cards need consistent row height.');
$expect(str_contains($css, '@media (max-width: 620px)'), 'Family gateways need a mobile breakpoint.');
if ($failures) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "internal family consistency contract: ok\n";
