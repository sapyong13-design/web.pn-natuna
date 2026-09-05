<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('_JEXEC', 1);
define('JDEBUG', false);
$root = dirname(__DIR__);
require $root . '/libraries/vendor/autoload.php';
require $root . '/plugins/system/lscache/lscache.php';
require $root . '/plugins/system/lscache/lscachebase.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

final class CsrfCacheInput extends Input
{
    public Input $post;
    public Input $get;
    public Input $server;

    public function __construct(array $data, string $method = 'GET')
    {
        parent::__construct($data);
        $this->post = new Input([]);
        $this->get = new Input([]);
        $this->server = new Input(['REQUEST_METHOD' => $method]);
    }
}

final class CsrfCacheApp
{
    public Input $input;
    public string $body = '';
    public array $headers = [];
    public object $client;
    public object $session;
    public Registry $config;

    public function __construct()
    {
        $this->client = (object) ['mobile' => false];
        $this->config = new Registry(['secret' => 'isolated-csrf-regression']);
        $this->session = new class {
            public User $user;
            public function __construct() { $this->user = new User(); }
            public function get($key, $default = null) { return $key === 'user' ? $this->user : $default; }
            public function getToken($forceNew = false) { return 'session-secret-not-public'; }
            public function isNew() { return false; }
        };
    }
    public function getSession() { return $this->session; }
    public function getConfig() { return $this->config; }
    public function getInput() { return $this->input; }
    public function getMenu() { return new class { public function getActive() { return (object) ['type' => 'component', 'id' => 1, 'home' => false, 'query' => ['option' => 'com_content']]; } }; }
    public function isClient($client) { return false; }
    public function get($key, $default = null) { return $this->config->get($key, $default); }
    public function getMessageQueue() { return []; }
    public function getBody() { return $this->body; }
    public function setBody($body) { $this->body = $body; }
    public function setHeader($name, $value, $replace = false) { $this->headers[$name] = $value; }
    public function getHeaders() { return []; }
    public function getResponse() { return new class { public function getStatusCode() { return 200; } }; }
    public function getTemplate() { return 'test'; }
}

final class CsrfCachePlugin extends plgSystemLSCache
{
    public function __construct($app)
    {
        $this->app = $app;
        $this->cacheEnabled = true;
        $this->settings = new Registry(['beforeRender' => 1]);
        $this->componentHelper = new class {
            public function registerEvents($option) {}
            public function supportComponent($option) { return false; }
        };
        $this->lscInstance = new class {
            public int $publicWrites = 0;
            public function config($config) {}
            public function cachePublic($tags, $esi = false) { ++$this->publicWrites; }
        };
        $this->purgeObject = (object) ['recacheAll' => false];
    }
    protected function isExcluded() { return false; }
    public function log($content = '', $logLevel = 0) {}
}
$expect = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$app = new CsrfCacheApp();
Factory::$application = $app;
$token = Session::getFormToken();
foreach (['post', 'get'] as $source) {
    $app->input = new CsrfCacheInput(['option' => 'com_content', 'lscache_formtoken' => '1'], strtoupper($source));
    $app->input->post = new Input($source === 'post' ? ['lscache_formtoken' => '1'] : []);
    $app->input->get = new Input($source === 'get' ? ['lscache_formtoken' => '1'] : []);
    $plugin = new CsrfCachePlugin($app);
    $plugin->onAfterRoute();
    $expect(!Session::checkToken(), 'Public marker must not authenticate a forged request from ' . $source);
    $app->input->post->set($token, '1');
    $expect(Session::checkToken(), 'Authentic session form must remain valid');
}

 $app->input = new CsrfCacheInput(['option' => 'com_content']);
foreach (['<input type="hidden" name="' . $token . '" value="1">', "<INPUT\nNAME = '$token' VALUE='1'>", '<script>const csrf = "' . $token . '";</script>'] as $body) {
    $app->body = '';
    $app->headers = [];
    $plugin = new CsrfCachePlugin($app);
    $plugin->onAfterRoute();
    // Old persisted beforeRender=1 settings must not publish an unfinished body.
    if (method_exists($plugin, 'onBeforeRender')) {
        $plugin->onBeforeRender();
    }
    $app->body = $body;
    $plugin->onAfterRender();
    $expect($app->body === $body, 'Session token must not be rewritten');
    $expect($plugin->lscInstance->publicWrites === 0, 'Token response must not enter public cache');
    $expect(($app->headers['Cache-Control'] ?? '') === 'private, no-store', 'Token response must reject shared storage');
}

$app->body = '<p>Public article without session data</p>';
$plugin = new CsrfCachePlugin($app);
$plugin->onAfterRoute();
$plugin->onAfterRender();
$expect($plugin->lscInstance->publicWrites === 1, 'Token-free page must remain cacheable');

$app->body = '';
$plugin = new CsrfCachePlugin($app);
$plugin->onAfterRoute();
$module = (object) ['content' => '<form><input name="' . $token . '" value="1"></form>'];
$original = $module->content;
$plugin->onAfterRenderModule($module);
$expect($module->content === $original && !$plugin->pageCachable, 'Token module must stay inline and exclude parent cache before ESI replacement');
echo "LSCache CSRF behavior: ok\n";
