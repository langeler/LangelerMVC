<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Core\Config;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;

class HttpSecurityManager
{
    use ArrayTrait, ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    public function __construct(
        private readonly Config $config,
        private readonly CacheManager $cache
    ) {
    }

    public function signUrl(string $path, array $params = [], ?int $expiresAt = null): string
    {
        $base = rtrim((string) $this->config->get('app', 'URL', ''), '/');
        $payload = $params;

        if ($expiresAt !== null) {
            $payload['expires'] = $expiresAt;
        }

        ksort($payload);
        $query = http_build_query($payload);
        $target = $base . $path . ($query !== '' ? '?' . $query : '');
        $signature = hash_hmac('sha256', $target, $this->signatureKey());
        $separator = $query === '' ? '?' : '&';

        return $target . $separator . 'signature=' . $signature;
    }

    public function hasValidSignature(string $url): bool
    {
        $parts = parse_url($url);
        $queryString = (string) ($parts['query'] ?? '');
        parse_str($queryString, $query);

        $signature = (string) ($query['signature'] ?? '');

        if ($signature === '') {
            return false;
        }

        unset($query['signature']);

        if (isset($query['expires']) && (int) $query['expires'] < time()) {
            return false;
        }

        ksort($query);
        $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'localhost');

        if (isset($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        $base .= (string) ($parts['path'] ?? '/');
        $rebuilt = $base . ($query !== [] ? '?' . http_build_query($query) : '');
        $expected = hash_hmac('sha256', $rebuilt, $this->signatureKey());

        return hash_equals($expected, $signature);
    }

    /**
     * @return array{allowed:bool,remaining:int,retry_after:int}
     */
    public function throttle(string $key, ?int $maxAttempts = null, ?int $decaySeconds = null): array
    {
        $maxAttempts ??= (int) $this->config->get('http', 'THROTTLE.MAX_ATTEMPTS', 5);
        $decaySeconds ??= (int) $this->config->get('http', 'THROTTLE.DECAY_SECONDS', 60);

        $bucketKey = 'throttle:' . trim($key);
        $bucket = $this->cache->get($bucketKey, [
            'attempts' => 0,
            'expires_at' => time() + $decaySeconds,
        ]);

        if (!is_array($bucket) || (int) ($bucket['expires_at'] ?? 0) <= time()) {
            $bucket = [
                'attempts' => 0,
                'expires_at' => time() + $decaySeconds,
            ];
        }

        $attempts = ((int) ($bucket['attempts'] ?? 0)) + 1;
        $bucket['attempts'] = $attempts;
        $retryAfter = max(0, ((int) $bucket['expires_at']) - time());
        $allowed = $attempts <= $maxAttempts;
        $this->cache->put($bucketKey, $bucket, $retryAfter > 0 ? $retryAfter : $decaySeconds);

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $maxAttempts - $attempts),
            'retry_after' => $allowed ? 0 : $retryAfter,
        ];
    }

    public function clearThrottle(string $key): bool
    {
        return $this->cache->forget('throttle:' . trim($key));
    }

    private function signatureKey(): string
    {
        $configured = (string) $this->config->get('http', 'SIGNED_URL.KEY', '');

        if ($configured !== '') {
            return $configured;
        }

        return (string) $this->config->get('app', 'NAME', 'LangelerMVC') . ':signed-url';
    }
}
