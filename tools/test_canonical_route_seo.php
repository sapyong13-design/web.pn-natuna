<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = (string) file_get_contents($root . '/database/migrations/20261019_canonicalize_duplicate_article_routes.sql');
$sitemap = (string) file_get_contents($root . '/tools/generate-sitemap.php');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void { if (!$condition) $failures[] = $message; };

$expect(str_contains($migration, "legacy.menutype='hidden'"), 'Only legacy hidden routes may be retired.');
$expect(str_contains($migration, "canonical.menutype='mainmenu'"), 'Published mainmenu route must be canonical.');
$expect(str_contains($migration, "CONCAT('/', legacy.path)"), 'Legacy URL must be registered as redirect source.');
$expect(str_contains($migration, "CONCAT('/', canonical.path)"), 'Hierarchical URL must be redirect target.');
$expect(str_contains($migration, 'SET legacy.published=0'), 'Legacy menu route must stop resolving as a duplicate 200.');
$expect(str_contains($sitemap, "menu.menutype='mainmenu'"), 'Sitemap must expose canonical mainmenu routes only.');
$expect(str_contains($sitemap, 'content.modified') && str_contains($sitemap, 'content.created'), 'Sitemap lastmod must use content timestamps.');
$expect(!str_contains($sitemap, "['/'=>gmdate('Y-m-d')]"), 'Sitemap must not claim every page changed on each cron run.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
printf("Canonical route SEO contract: ok\n");
