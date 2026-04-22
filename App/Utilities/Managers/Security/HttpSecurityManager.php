<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Core\Config;
use App\Core\Session;
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

    public function csrfEnabled(): bool
    {
        return $this->normalizeBool($this->config->get('http', 'CSRF.ENABLED', true), true);
    }

    public function csrfField(): string
    {
        $configured = $this->trimString((string) $this->config->get('http', 'CSRF.FIELD', '_token'));

        return $configured !== '' ? $configured : '_token';
    }

    public function csrfHeader(): string
    {
        $configured = $this->trimString((string) $this->config->get('http', 'CSRF.HEADER', 'X-CSRF-TOKEN'));

        return $configured !== '' ? $configured : 'X-CSRF-TOKEN';
    }

    public function csrfToken(Session $session): string
    {
        return $session->token();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     */
    public function hasValidCsrfToken(Session $session, array $payload = [], array $headers = []): bool
    {
        if (!$this->csrfEnabled()) {
            return true;
        }

        $token = $this->trimString((string) $this->extractCsrfToken($payload, $headers));

        if ($token === '') {
            return false;
        }

        return hash_equals($session->token(), $token);
    }

    public function requiresCsrfProtection(string $method, ?bool $override = null): bool
    {
        if ($override !== null) {
            return $override;
        }

        return $this->csrfEnabled()
            && !in_array(strtoupper($this->trimString($method)), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * @return array<string, string>
     */
    public function defaultHeaders(bool $secureRequest = false): array
    {
        $headers = [
            'Content-Security-Policy' => (string) $this->config->get(
                'http',
                'HEADERS.CONTENT_SECURITY_POLICY',
                "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'"
            ),
            'Permissions-Policy' => (string) $this->config->get(
                'http',
                'HEADERS.PERMISSIONS_POLICY',
                'camera=(), geolocation=(), microphone=(), payment=()'
            ),
            'Referrer-Policy' => (string) $this->config->get(
                'http',
                'HEADERS.REFERRER_POLICY',
                'strict-origin-when-cross-origin'
            ),
            'X-Content-Type-Options' => (string) $this->config->get(
                'http',
                'HEADERS.X_CONTENT_TYPE_OPTIONS',
                'nosniff'
            ),
            'X-Frame-Options' => (string) $this->config->get(
                'http',
                'HEADERS.X_FRAME_OPTIONS',
                'SAMEORIGIN'
            ),
            'Cross-Origin-Opener-Policy' => (string) $this->config->get(
                'http',
                'HEADERS.CROSS_ORIGIN_OPENER_POLICY',
                'same-origin'
            ),
            'Cross-Origin-Resource-Policy' => (string) $this->config->get(
                'http',
                'HEADERS.CROSS_ORIGIN_RESOURCE_POLICY',
                'same-origin'
            ),
        ];

        if ($secureRequest) {
            $headers['Strict-Transport-Security'] = (string) $this->config->get(
                'http',
                'HEADERS.STRICT_TRANSPORT_SECURITY',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $this->filterHeaders($headers);
    }

    public function decorateHtmlDocument(string $html, Session $session): string
    {
        if ($this->trimString($html) === '') {
            return $html;
        }

        $token = htmlspecialchars($this->csrfToken($session), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $field = htmlspecialchars($this->csrfField(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = $this->injectCsrfMeta($html, $token);
        $html = $this->injectCsrfBootstrap($html);
        $html = $this->injectCsrfFields($html, $token, $field);

        return $html;
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

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     */
    private function extractCsrfToken(array $payload, array $headers): mixed
    {
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[$this->toLowerString($this->trimString((string) $name))] = $value;
        }

        $header = $this->toLowerString($this->csrfHeader());
        $alternate = 'x-xsrf-token';
        $field = $this->csrfField();

        return $payload[$field]
            ?? $normalizedHeaders[$header]
            ?? $normalizedHeaders[$alternate]
            ?? null;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function filterHeaders(array $headers): array
    {
        $filtered = [];

        foreach ($headers as $name => $value) {
            $normalizedName = $this->trimString($name);
            $normalizedValue = $this->trimString($value);

            if ($normalizedName === '' || $normalizedValue === '') {
                continue;
            }

            $filtered[$normalizedName] = $normalizedValue;
        }

        return $filtered;
    }

    private function injectCsrfMeta(string $html, string $token): string
    {
        if (preg_match('/<meta\s+name=["\']csrf-token["\']/i', $html) === 1) {
            return $html;
        }

        $meta = '<meta name="csrf-token" content="' . $token . '">' . PHP_EOL;

        if (preg_match('/<\/head>/i', $html) === 1) {
            return preg_replace('/<\/head>/i', $meta . '</head>', $html, 1) ?? $html;
        }

        return $meta . $html;
    }

    private function injectCsrfBootstrap(string $html): string
    {
        if (str_contains($html, 'data-langeler-security="csrf-bootstrap"')) {
            return $html;
        }

        $script = <<<'HTML'
<script data-langeler-security="csrf-bootstrap">
    (() => {
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            return;
        }

        const meta = document.querySelector('meta[name="csrf-token"]');
        const token = meta ? String(meta.getAttribute('content') || '') : '';
        window.LangelerSecurity = Object.assign({}, window.LangelerSecurity || {}, { csrfToken: token });

        if (typeof window.fetch !== 'function' || window.fetch.__langelerCsrfWrapped === true) {
            return;
        }

        const originalFetch = window.fetch.bind(window);

        const resolveMethod = (input, init) => {
            if (init && typeof init.method === 'string' && init.method !== '') {
                return init.method.toUpperCase();
            }

            if (typeof Request !== 'undefined' && input instanceof Request) {
                return String(input.method || 'GET').toUpperCase();
            }

            return 'GET';
        };

        const resolveUrl = (input) => {
            if (typeof Request !== 'undefined' && input instanceof Request) {
                return input.url;
            }

            return String(input || '');
        };

        const shouldAttachToken = (input, init) => {
            const method = resolveMethod(input, init);

            if (['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                return false;
            }

            const url = resolveUrl(input);

            try {
                const target = new URL(url, window.location.href);
                return target.origin === window.location.origin;
            } catch (error) {
                return true;
            }
        };

        window.fetch = (input, init = {}) => {
            if (!token || !shouldAttachToken(input, init)) {
                return originalFetch(input, init);
            }

            const headers = new Headers(
                init.headers
                    || (typeof Request !== 'undefined' && input instanceof Request ? input.headers : undefined)
                    || {}
            );

            if (!headers.has('X-CSRF-TOKEN')) {
                headers.set('X-CSRF-TOKEN', token);
            }

            return originalFetch(input, { ...init, headers });
        };

        window.fetch.__langelerCsrfWrapped = true;
    })();
</script>
HTML;

        if (preg_match('/<\/head>/i', $html) === 1) {
            return preg_replace('/<\/head>/i', $script . PHP_EOL . '</head>', $html, 1) ?? $html;
        }

        return $script . PHP_EOL . $html;
    }

    private function injectCsrfFields(string $html, string $token, string $field): string
    {
        return preg_replace_callback(
            '/<form\b(?=[^>]*\bmethod\s*=\s*(["\']?)(post|put|patch|delete)\1)([^>]*)>/i',
            static function (array $matches) use ($token, $field): string {
                $openingTag = $matches[0] ?? '<form>';

                if (str_contains($openingTag, 'data-csrf-protected="1"')) {
                    return $openingTag;
                }

                $hidden = '<input type="hidden" name="' . $field . '" value="' . $token . '">';

                return rtrim(substr($openingTag, 0, -1)) . ' data-csrf-protected="1">' . $hidden;
            },
            $html
        ) ?? $html;
    }

    private function normalizeBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (!is_string($value)) {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }
}
