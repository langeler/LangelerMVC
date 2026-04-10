<?php

declare(strict_types=1);

namespace App\Drivers\Passkeys;

use App\Contracts\Support\PasskeyDriverInterface;
use App\Exceptions\AuthException;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use ParagonIE\ConstantTime\Base64UrlSafe;

class TestingPasskeyDriver implements PasskeyDriverInterface
{
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait;

    public function name(): string
    {
        return 'testing';
    }

    public function capabilities(): array
    {
        return [
            'registration' => true,
            'authentication' => true,
            'resident_keys' => true,
            'user_verification' => true,
            'testing' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return $this->resolveCapability($feature) === true;
    }

    public function beginRegistration(array $context): array
    {
        return [
            'options' => [
                'challenge' => Base64UrlSafe::encodeUnpadded((string) $context['challenge']),
                'timeout' => (int) ($context['timeout'] ?? 60000),
                'rp' => [
                    'name' => (string) ($context['rp_name'] ?? 'LangelerMVC'),
                    'id' => (string) ($context['rp_id'] ?? 'localhost'),
                ],
                'user' => [
                    'name' => (string) ($context['user_name'] ?? ''),
                    'id' => Base64UrlSafe::encodeUnpadded((string) ($context['user_handle'] ?? '')),
                    'displayName' => (string) ($context['display_name'] ?? ''),
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],
                    ['type' => 'public-key', 'alg' => -257],
                ],
                'authenticatorSelection' => [
                    'authenticatorAttachment' => $context['attachment'] ?? null,
                    'residentKey' => $context['resident_key'] ?? 'preferred',
                    'userVerification' => $context['user_verification'] ?? 'preferred',
                ],
                'attestation' => (string) ($context['attestation'] ?? 'none'),
                'excludeCredentials' => array_values(array_map(
                    fn(array $credential): array => [
                        'type' => 'public-key',
                        'id' => (string) ($credential['id'] ?? ''),
                        'transports' => array_values(array_map('strval', (array) ($credential['transports'] ?? []))),
                    ],
                    (array) ($context['exclude_credentials'] ?? [])
                )),
            ],
        ];
    }

    public function finishRegistration(array $credential, array $context): array
    {
        $credentialId = $this->extractCredentialId($credential);

        if ($credentialId === '') {
            throw new AuthException('Testing passkey registration requires a credential identifier.');
        }

        $source = [
            'driver' => $this->name(),
            'publicKeyCredentialId' => $credentialId,
            'type' => 'public-key',
            'transports' => array_values(array_map('strval', (array) ($credential['transports'] ?? ['internal']))),
            'attestationType' => 'none',
            'credentialPublicKey' => Base64UrlSafe::encodeUnpadded(hash('sha256', $credentialId, true)),
            'userHandle' => Base64UrlSafe::encodeUnpadded((string) ($context['user_handle'] ?? '')),
            'counter' => 0,
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'backupEligible' => true,
            'backupStatus' => false,
        ];

        return [
            'credentialId' => $credentialId,
            'source' => $source,
            'counter' => 0,
        ];
    }

    public function beginAuthentication(array $context): array
    {
        return [
            'options' => [
                'challenge' => Base64UrlSafe::encodeUnpadded((string) ($context['challenge'] ?? '')),
                'timeout' => (int) ($context['timeout'] ?? 60000),
                'rpId' => (string) ($context['rp_id'] ?? 'localhost'),
                'userVerification' => (string) ($context['user_verification'] ?? 'preferred'),
                'allowCredentials' => array_values(array_map(
                    fn(array $credential): array => [
                        'type' => 'public-key',
                        'id' => (string) ($credential['id'] ?? ''),
                        'transports' => array_values(array_map('strval', (array) ($credential['transports'] ?? []))),
                    ],
                    (array) ($context['allowed_credentials'] ?? [])
                )),
            ],
        ];
    }

    public function finishAuthentication(array $credential, array $storedCredential, array $context): array
    {
        $credentialId = $this->extractCredentialId($credential);
        $source = $this->isArray($storedCredential['source'] ?? null) ? $storedCredential['source'] : [];

        if ($credentialId === '' || $credentialId !== (string) ($source['publicKeyCredentialId'] ?? '')) {
            throw new AuthException('Testing passkey authentication failed because the credential could not be matched.');
        }

        $source['counter'] = ((int) ($source['counter'] ?? 0)) + 1;
        $source['backupStatus'] = (bool) ($source['backupStatus'] ?? false);

        return [
            'credentialId' => $credentialId,
            'source' => $source,
            'counter' => (int) $source['counter'],
        ];
    }

    public function extractCredentialId(array $credential): string
    {
        $id = $credential['id'] ?? $credential['rawId'] ?? '';

        return $this->isString($id) ? $this->trimString($id) : '';
    }

    private function resolveCapability(string $feature): mixed
    {
        $value = $this->capabilities();

        foreach (explode('.', $this->trimString($feature)) as $segment) {
            if (!$this->isArray($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
