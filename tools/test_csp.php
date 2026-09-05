<?php
/** Trusted head scripts work; arbitrary page scripts and event handlers never gain permission. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require JPATH_BASE . '/includes/pn-csp.php';

$document = new Joomla\CMS\Document\HtmlDocument();
$trusted = "window.trustedCsp = 1;\r\n";
$injected = 'window.injectedCsp = 1;';
$document->addScriptDeclaration($trusted);
$document->getWebAssetManager()->addInlineScript('window.trustedWam = 1;');
$renderer = new Joomla\CMS\Document\Renderer\Html\ScriptsRenderer($document);
$policy = pnNatunaContentSecurityPolicy($renderer->render(null), false);
$hash = static fn (string $value): string => "'sha256-" . base64_encode(hash('sha256', $value, true)) . "'";
$expect = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$expect(str_contains($policy, $hash("window.trustedCsp = 1;\n")), 'Legacy declaration must execute after HTML newline normalization.');
$expect(str_contains($policy, $hash('window.trustedWam = 1;')), 'WAM declaration must execute.');
$expect(!str_contains($policy, $hash($injected)), 'Unregistered injected script must stay blocked.');
$expect(str_contains($policy, "script-src-attr 'none'"), 'Injected event handlers must stay blocked.');
preg_match('~(?:^|; )script-src ([^;]+)~', $policy, $scriptPolicy);
$expect(!str_contains($scriptPolicy[1], 'unsafe-inline') && !str_contains($scriptPolicy[1], 'unsafe-eval'), 'Script policy must not broadly authorize inline code/eval.');
$expect(!str_contains($policy, 'upgrade-insecure-requests'), 'Local HTTP must remain usable.');
$expect(str_contains(pnNatunaContentSecurityPolicy('', true), 'upgrade-insecure-requests'), 'HTTPS must upgrade mixed resources.');
echo "CSP trusted-script regression: ok\n";
