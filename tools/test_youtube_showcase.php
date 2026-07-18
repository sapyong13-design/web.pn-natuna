<?php
/** Focused source contract for lazy YouTube showcase behavior. */
$source = (string) file_get_contents(dirname(__DIR__) . '/templates/pn_natuna_2026/js/template.js');
$css = (string) file_get_contents(dirname(__DIR__) . '/templates/pn_natuna_2026/css/template.css');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expect((bool) preg_match('/function\s+setupYouTubeShowcase\s*\(\s*\)/', $source), 'YouTube showcase setup function is missing.');
$expect(str_contains($source, 'setupYouTubeShowcase();'), 'YouTube showcase setup is not invoked on DOMContentLoaded.');
$expect(str_contains($source, "[data-youtube-showcase]"), 'Showcase root selector is missing.');
$expect(str_contains($source, "[data-youtube-player]"), 'Player selector is missing.');
$expect(str_contains($source, "[data-youtube-preview]"), 'Preview selector is missing.');
$expect(str_contains($source, "[data-youtube-play]"), 'Play selector is missing.');
$expect(str_contains($source, "[data-youtube-title]"), 'Title selector is missing.');
$expect(str_contains($source, "[data-youtube-fallback]"), 'Fallback selector is missing.');
$expect(str_contains($source, "[data-youtube-status]"), 'Live status selector is missing.');
$expect(str_contains($source, "[data-youtube-item]"), 'Rail item selector is missing.');
$expect((bool) preg_match('/\^\[A-Za-z0-9_-\]\{11\}\$/', $source), 'Video ID validation must require exactly 11 safe characters.');
$expect(str_contains($source, 'https://www.youtube-nocookie.com/embed/'), 'Player must use youtube-nocookie embed URL.');
$expect(str_contains($source, 'https://www.youtube.com/watch?v='), 'Canonical YouTube fallback URL is missing.');
$expect(str_contains($source, "iframe.setAttribute('title'"), 'Iframe title is missing.');
$expect(str_contains($source, "iframe.setAttribute('allow'"), 'Iframe allow policy is missing.');
$expect(str_contains($source, "iframe.setAttribute('allowfullscreen'"), 'Iframe allowfullscreen is missing.');
$expect(str_contains($source, "iframe.setAttribute('referrerpolicy'"), 'Iframe referrer policy is missing.');
$expect(str_contains($source, "button.classList.toggle('is-active'"), 'Rail active state is missing.');
$expect(str_contains($source, "button.setAttribute('aria-current'"), 'Rail current state is missing.');
$expect((bool) preg_match("/state\.textContent\s*=\s*current\s*\?\s*'Sedang dipilih'/", $source), 'Rail must expose a clear selected-state label.');
$expect(str_contains($source, "source.textContent = selected.source === 'wajib' ? 'Video pilihan' : 'Video terbaru'"), 'Player context must follow selected video source.');
$expect(!str_contains($source, 'youtube.com/iframe_api'), 'Showcase must not load the YouTube iframe API.');
$expect(!str_contains($source, 'youtube-nocookie.com/embed/PLACEHOLDER'), 'Showcase must not contain a placeholder iframe.');
$expect(str_contains($source, "player.classList.add('is-playing')"), 'Playback must set an explicit player is-playing state.');
$expect((bool) preg_match('/\.youtube-showcase-iframe\s*\{[^}]*position:\s*absolute;[^}]*inset:\s*0;[^}]*width:\s*100%;[^}]*height:\s*100%;[^}]*border:\s*0;/s', $css), 'Iframe must absolutely fill the 16:9 player without a border.');
$expect((bool) preg_match('/\.youtube-showcase-player\.is-playing\s*>\s*img,\s*\.youtube-showcase-player\.is-playing\s+\.youtube-showcase-player__shade,\s*\.youtube-showcase-player\.is-playing\s+\.youtube-showcase-play,\s*\.youtube-showcase-player\.is-playing\s+\.youtube-showcase-player__copy\s*\{[^}]*display:\s*none;[^}]*pointer-events:\s*none;/s', $css), 'Playback state must hide preview image, shade, play control, and copy without intercepting player input.');
$expect((bool) preg_match('/if\s*\(iframe\)\s*\{\s*iframe\.title\s*=\s*`Video YouTube: \$\{selected\.title\}`;\s*iframe\.src\s*=\s*`https:\/\/www\.youtube-nocookie\.com\/embed\/\$\{selected\.id\}\?autoplay=1&rel=0`;/s', $source), 'Selecting a rail item after playback must update the existing iframe title and source.');
$expect((bool) preg_match('/\.youtube-showcase-player__copy\s*\{[^}]*bottom:\s*16px;[^}]*\}/s', $css), 'Preview copy must stay in the player bottom safe area.');
$expect((bool) preg_match('/\.youtube-showcase-player__copy strong\s*\{[^}]*color:\s*var\(--color-accent-soft\);[^}]*\}/s', $css), 'Preview title must use explicit solid light text over the thumbnail.');
$expect((bool) preg_match('/\.youtube-showcase-player__copy a\s*\{[^}]*color:\s*var\(--color-accent-soft\);[^}]*\}/s', $css), 'Preview fallback must use explicit solid light text over the thumbnail.');
$expect((bool) preg_match('/body\.is-dark \.youtube-showcase-player__copy strong,\s*body\.is-dark \.youtube-showcase-player__copy a\s*\{[^}]*color:\s*var\(--color-accent-soft\);[^}]*\}/s', $css), 'Dark mode must preserve solid light preview title and fallback text.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\)\s*\{.*?\.youtube-showcase-player__copy\s*\{[^}]*right:\s*12px;[^}]*bottom:\s*12px;[^}]*left:\s*12px;[^}]*flex-direction:\s*row;[^}]*\}.*?\.youtube-showcase-player__copy strong\s*\{[^}]*-webkit-line-clamp:\s*2;[^}]*\}/s', $css), 'Mobile preview copy must remain compact in a bottom safe area, spatially separate from the centered play control.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "youtube showcase contract: ok\n";
