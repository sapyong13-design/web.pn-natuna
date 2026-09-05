<?php
/** Hero teasers: execute real Joomla query against disposable SQLite fixtures. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/libraries/bootstrap.php';
require_once JPATH_BASE . '/templates/pn_natuna_2026/hero-slider.php';

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
Factory::$language = Joomla\CMS\Language\Language::getInstance('en-GB');


$user = new class extends User {
    public function allow(array $levels): void
    {
        $this->_authLevels = $levels;
    }
};
Factory::$application = new class ($user) {
    public function __construct(private User $user) {}
    public function getSession(): object
    {
        return new class ($this->user) {
            public function __construct(private User $user) {}
            public function get(string $key): User { return $this->user; }
        };
    }
};
$db = DatabaseDriver::getInstance(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'test_']);
Factory::$database = $db;
$db->setQuery('CREATE TABLE test_categories (id INTEGER PRIMARY KEY, access INTEGER, published INTEGER)')->execute();
$db->setQuery('CREATE TABLE test_content (id INTEGER PRIMARY KEY, title TEXT, alias TEXT, catid INTEGER, created TEXT, publish_up TEXT, publish_down TEXT, images TEXT, introtext TEXT, fulltext TEXT, metadesc TEXT, state INTEGER, access INTEGER)')->execute();
$db->setQuery('INSERT INTO test_categories VALUES (12, 1, 1), (13, 2, 1), (14, 1, 0)')->execute();
$insert = static function (int $id, array $changes = []) use ($db): void {
    $row = (object) array_replace([
        'id' => $id, 'title' => 'Article ' . $id, 'alias' => 'article-' . $id,
        'catid' => 12, 'created' => '2001-01-01 00:00:00',
        'publish_up' => '2002-01-01 00:00:00', 'publish_down' => null,
        'images' => '', 'introtext' => 'Teaser', 'fulltext' => '', 'metadesc' => '',
        'state' => 1, 'access' => 1,
    ], $changes);
    $db->insertObject('#__content', $row);
};
$insert(1);
$insert(2, ['access' => 2, 'publish_up' => '2004-01-01 00:00:00']);
$insert(3, ['publish_up' => '2999-01-01 00:00:00']);
$insert(4, ['publish_down' => '2000-01-01 00:00:00']);
$insert(5, ['state' => 0]);
$insert(6, ['alias' => 'berita-dan-pengumuman']);
$insert(7, ['publish_up' => null, 'created' => '2003-01-01 00:00:00']);
$insert(8, ['catid' => 13]);
$insert(9, ['catid' => 14]);
$insert(10, ['publish_up' => $db->getNullDate(), 'publish_down' => $db->getNullDate()]);
$expect = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$ids = static fn(array $items): array => array_map(static fn(object $item): int => (int) $item->id, $items);
$user->allow([1]);
$guest = pn_natuna_hero_latest_articles(12, 20);
$expect($ids($guest) === [7, 1, 10], 'Guest sees only published public articles in effective-date order.');
$expect($guest[1]->created === '2002-01-01 00:00:00', 'Teaser date uses publication date.');
$expect(pn_natuna_hero_latest_articles(13) === [], 'Guest must not see public articles in restricted category.');
$expect(pn_natuna_hero_latest_articles(14) === [], 'Unpublished category must not expose teasers.');
$user->allow([1, 2]);
$expect($ids(pn_natuna_hero_latest_articles(12, 2)) === [2, 7], 'Authorized article participates in ordering before limit.');
$expect($ids(pn_natuna_hero_latest_articles(13)) === [8], 'Authorized visitor sees restricted category.');
$expect(pn_natuna_hero_latest_articles(14) === [], 'Authorization must not bypass category publication.');
echo "Hero article access regression passed.\n";
