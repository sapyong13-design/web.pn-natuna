<?php
$root = dirname(__DIR__);
$css = file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expect(str_contains($css, '/* HOMEPAGE VISUAL CONSISTENCY 2026-07-18 */'), 'Homepage consistency layer is missing.');
foreach (['--home-radius-control', '--home-radius-card', '--home-radius-overlay', '--home-shadow-interactive', '--home-shadow-overlay', '--home-section-gap'] as $token) {
    $expect(str_contains($css, $token . ':'), "Homepage visual token missing: {$token}");
}
$expect((bool) preg_match('/body\.is-home \.home-juknis-main > \.module-card\s*\{[^}]*border-inline:\s*0;[^}]*border-radius:\s*0;[^}]*box-shadow:\s*none/s', $css), 'Structural homepage modules must use a flat editorial surface.');
$expect((bool) preg_match('/body\.is-home \.home-juknis-sidebar > \.module-card[^}]*border-radius:\s*var\(--home-radius-card\)/s', $css), 'Interactive sidebar modules need one shared card radius.');
$expect((bool) preg_match('/body\.is-home \.section-kicker\s*\{[^}]*font-size:\s*\.76rem;[^}]*letter-spacing:\s*\.08em/s', $css), 'Homepage kicker typography must be calmer and more readable.');
$expect((bool) preg_match('/body\.is-home \.section-desc\s*\{[^}]*max-width:\s*68ch/s', $css), 'Homepage descriptions need a readable line length.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home :is\([^}]*font-size:\s*max\(\.78rem, 14px\)/s', $css), 'Important mobile UI copy needs a 14px floor.');
$expect((bool) preg_match('/body\.is-home \.module-card\.facility-band\s*\{[^}]*background:\s*#[0-9a-fA-F]{6}/s', $css), 'Facility section must use a committed solid surface instead of layered gradients.');
$expect((bool) preg_match('/body\.is-home \.home-section-divider\s*\{[^}]*display:\s*none/s', $css), 'Redundant standalone homepage dividers must be removed.');
$expect(!str_contains($css, 'body.is-home .module-card { backdrop-filter:'), 'Homepage cards must not introduce glass effects.');
$expect(str_contains($css, '--home-section-gap: clamp(28px, 3vw, 40px);'), 'Desktop homepage section gap must use the compact rhythm.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home \.home-juknis-main\s*\{[^}]*gap:\s*22px/s', $css), 'Mobile main sections need a 22px gap.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home \.home-juknis-sidebar\s*\{[^}]*gap:\s*20px/s', $css), 'Mobile sidebar cards need a 20px gap.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home \.home-juknis-main > \.module-card\s*\{[^}]*padding-block:\s*18px/s', $css), 'Mobile flat sections need compact 18px vertical padding.');
$expect((bool) preg_match('/@media \(min-width:\s*761px\).*?body\.is-home \.home-juknis-main > :is\([^}]*padding-inline:\s*24px/s', $css), 'Desktop main sections need one shared 24px horizontal inset.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home \.mobile-start-here\s*\{[^}]*padding-bottom:\s*24px/s', $css), 'Mobile start section must not leave an oversized trailing gap.');
$expect((bool) preg_match('/@media \(min-width:\s*761px\).*?body\.is-home \.home-map-card\s*\{[^}]*width:\s*calc\(100% \+ 48px\);[^}]*margin-inline:\s*-24px/s', $css), 'Desktop location card must fill the same wrapper width as other full cards.');
$expect((bool) preg_match('/@media \(min-width:\s*1181px\).*?body\.is-home \.announcement-showcase__grid\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\)/s', $css), 'Desktop announcement and video cards must use equal columns.');
$expect((bool) preg_match('/body\.is-home \.announcement-feature,\s*body\.is-home \.youtube-showcase\s*\{[^}]*padding:\s*14px;[^}]*border-radius:\s*var\(--home-radius-card\)/s', $css), 'Announcement and video cards need one shared shell.');
$expect((bool) preg_match('/body\.is-home \.announcement-feature__media\s*\{[^}]*margin-inline:\s*0;[^}]*aspect-ratio:\s*16 \/ 9/s', $css), 'Announcement media must align with the video aspect ratio.');
$expect((bool) preg_match('/body\.is-home \.announcement-feature strong\s*\{[^}]*font-family:\s*var\(--font-body\)/s', $css), 'Announcement title must use the same UI type language as video content.');
$expect((bool) preg_match('/body\.is-home \.home-juknis-main > \.announcement-showcase\s*\{[^}]*border:\s*1px solid var\(--home-rule\);[^}]*border-radius:\s*var\(--home-radius-card\);[^}]*background:\s*var\(--home-paper\)/s', $css), 'Announcement showcase heading and content must share one outer card.');
$expect((bool) preg_match('/body\.is-home \.announcement-showcase :is\(\.announcement-feature, \.youtube-showcase\)\s*\{[^}]*border:\s*0;[^}]*border-radius:\s*var\(--home-radius-control\);[^}]*background:\s*transparent/s', $css), 'Showcase columns must become light internal panels, not nested cards.');
$expect((bool) preg_match('/body\.is-home \.announcement-showcase__actions > :is\(\.section-action, \.announcement-showcase__channel-link\)\s*\{[^}]*display:\s*inline-flex;[^}]*min-height:\s*44px;[^}]*align-items:\s*center;[^}]*justify-content:\s*center;[^}]*border:\s*1px solid var\(--home-rule\);[^}]*padding:\s*10px 16px;[^}]*text-align:\s*center/s', $css), 'Showcase actions must share one centered button pattern.');
$expect((bool) preg_match('/body\.is-home \.home-juknis-main > \.module-card:has\(\.maklumat-compact\)\s*\{[^}]*border:\s*1px solid var\(--home-rule\);[^}]*border-radius:\s*var\(--home-radius-card\);[^}]*background:\s*var\(--home-paper\)/s', $css), 'Maklumat must use the same outer card surface as other homepage sections.');
$expect((bool) preg_match('/body\.is-home \.maklumat-compact-doc\s*\{[^}]*grid-template-columns:\s*minmax\(112px, 128px\) minmax\(0, 1fr\)/s', $css), 'Desktop Maklumat documents need larger poster thumbnails.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.is-home \.instansi-tabbar\s*\{[^}]*justify-content:\s*flex-start/s', $css), 'Mobile institution tabs must wrap from the left edge.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "homepage visual consistency contract: ok\n";
