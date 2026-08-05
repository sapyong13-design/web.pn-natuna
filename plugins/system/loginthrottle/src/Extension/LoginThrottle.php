<?php
declare(strict_types=1);

namespace PNNatuna\Plugin\System\LoginThrottle\Extension;

use Joomla\CMS\Event\User\AfterLoginEvent;
use Joomla\CMS\Event\User\LoginFailureEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

final class LoginThrottle extends CMSPlugin implements SubscriberInterface
{
    private const WINDOW = 900;
    private const MAX_FAILURES = 5;
    private const BLOCK_SECONDS = 1800;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'enforce',
            'onUserLoginFailure' => 'recordFailure',
            'onUserAfterLogin' => 'clearFailures',
        ];
    }

    public function enforce(): void
    {
        if (!$this->getApplication()->isClient('administrator') || !$this->isLoginPost()) return;
        $state = $this->mutate(static fn(array $state, int $now): array => [$state, $state]);
        if (($state['blocked_until'] ?? 0) > time()) {
            $this->getApplication()->setHeader('Retry-After', (string) (($state['blocked_until'] ?? time()) - time()), true);
            throw new \RuntimeException('Terlalu banyak percobaan login. Coba kembali dalam 30 menit.', 429);
        }
    }

    public function recordFailure(LoginFailureEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) return;
        $this->mutate(static function (array $state, int $now): array {
            $failures = array_values(array_filter($state['failures'] ?? [], static fn($ts): bool => is_int($ts) && $ts >= $now - self::WINDOW));
            $failures[] = $now;
            $state = ['failures' => $failures, 'blocked_until' => count($failures) >= self::MAX_FAILURES ? $now + self::BLOCK_SECONDS : 0];
            return [$state, $state];
        });
    }

    public function clearFailures(AfterLoginEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) return;
        $file = $this->stateFile();
        if (is_file($file)) @unlink($file);
    }

    private function isLoginPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            && (($_POST['option'] ?? '') === 'com_login' || ($_POST['task'] ?? '') === 'login');
    }

    private function stateFile(): string
    {
        $forwarded = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0]);
        $ip = filter_var($forwarded, FILTER_VALIDATE_IP) ? $forwarded : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $dir = dirname(JPATH_ROOT) . '/private/login-throttle';
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . hash('sha256', $ip) . '.json';
    }

    private function mutate(callable $callback): mixed
    {
        $file = $this->stateFile();
        $handle = fopen($file, 'c+');
        if (!$handle || !flock($handle, LOCK_EX)) return [];
        $raw = stream_get_contents($handle);
        $state = is_string($raw) ? json_decode($raw, true) : [];
        [$next, $result] = $callback(is_array($state) ? $state : [], time());
        rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode($next, JSON_UNESCAPED_SLASHES)); fflush($handle);
        flock($handle, LOCK_UN); fclose($handle); chmod($file, 0600);
        return $result;
    }
}
