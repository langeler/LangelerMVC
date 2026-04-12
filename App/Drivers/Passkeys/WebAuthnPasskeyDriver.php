<?php

declare(strict_types=1);

namespace App\Drivers\Passkeys;

use App\Contracts\Support\PasskeyDriverInterface;
use App\Exceptions\AuthException;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnPasskeyDriver implements PasskeyDriverInterface
{
    use ArrayTrait, CheckerTrait, ConversionTrait, ManipulationTrait, TypeCheckerTrait;

    private ?\Symfony\Component\Serializer\SerializerInterface $serializer = null;

    public function name(): string
    {
        return 'webauthn';
    }

    public function capabilities(): array
    {
        return [
            'registration' => true,
            'authentication' => true,
            'resident_keys' => true,
            'user_verification' => true,
            'attestation' => [
                'none' => true,
            ],
            'serializer' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return $this->resolveCapability($feature) === true;
    }

    public function beginRegistration(array $context): array
    {
        $options = PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create(
                (string) ($context['rp_name'] ?? 'LangelerMVC'),
                (string) ($context['rp_id'] ?? 'localhost')
            ),
            PublicKeyCredentialUserEntity::create(
                (string) ($context['user_name'] ?? ''),
                (string) ($context['user_handle'] ?? ''),
                (string) ($context['display_name'] ?? '')
            ),
            (string) ($context['challenge'] ?? ''),
            [
                PublicKeyCredentialParameters::createPk(-7),
                PublicKeyCredentialParameters::createPk(-257),
            ],
            AuthenticatorSelectionCriteria::create(
                $this->stringOrNull($context['attachment'] ?? null),
                (string) ($context['user_verification'] ?? 'preferred'),
                $this->stringOrNull($context['resident_key'] ?? 'preferred')
            ),
            (string) ($context['attestation'] ?? PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE),
            $this->credentialDescriptors((array) ($context['exclude_credentials'] ?? [])),
            (int) ($context['timeout'] ?? 60000)
        );

        return [
            'options' => $this->serializer()->normalize($options),
        ];
    }

    public function finishRegistration(array $credential, array $context): array
    {
        $publicKeyCredential = $this->serializer()->denormalize($credential, PublicKeyCredential::class);

        if (!$publicKeyCredential instanceof PublicKeyCredential || !$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new AuthException('Invalid passkey registration payload.');
        }

        $options = $this->serializer()->denormalize(
            (array) ($context['options'] ?? []),
            PublicKeyCredentialCreationOptions::class
        );

        if (!$options instanceof PublicKeyCredentialCreationOptions) {
            throw new AuthException('Unable to restore the stored passkey registration challenge.');
        }

        $validator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyFactory($context)->creationCeremony()
        );
        $source = $validator->check(
            $publicKeyCredential->response,
            $options,
            (string) ($context['rp_id'] ?? 'localhost')
        );

        return [
            'credentialId' => $this->serializer()->normalize($source)['publicKeyCredentialId'] ?? $this->extractCredentialId($credential),
            'source' => $this->serializer()->normalize($source),
            'counter' => (int) $source->counter,
        ];
    }

    public function beginAuthentication(array $context): array
    {
        $options = PublicKeyCredentialRequestOptions::create(
            (string) ($context['challenge'] ?? ''),
            (string) ($context['rp_id'] ?? 'localhost'),
            $this->credentialDescriptors((array) ($context['allowed_credentials'] ?? [])),
            (string) ($context['user_verification'] ?? 'preferred'),
            (int) ($context['timeout'] ?? 60000)
        );

        return [
            'options' => $this->serializer()->normalize($options),
        ];
    }

    public function finishAuthentication(array $credential, array $storedCredential, array $context): array
    {
        $publicKeyCredential = $this->serializer()->denormalize($credential, PublicKeyCredential::class);

        if (!$publicKeyCredential instanceof PublicKeyCredential || !$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            throw new AuthException('Invalid passkey authentication payload.');
        }

        $options = $this->serializer()->denormalize(
            (array) ($context['options'] ?? []),
            PublicKeyCredentialRequestOptions::class
        );

        if (!$options instanceof PublicKeyCredentialRequestOptions) {
            throw new AuthException('Unable to restore the stored passkey authentication challenge.');
        }

        $sourcePayload = $storedCredential['source'] ?? null;
        if ($this->isString($sourcePayload)) {
            try {
                $normalizedSource = $this->fromJson($sourcePayload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new AuthException('The stored passkey source payload is invalid.', 0, $exception);
            }
        } else {
            $normalizedSource = $sourcePayload;
        }

        if (!$this->isArray($normalizedSource)) {
            throw new AuthException('The stored passkey source payload is invalid.');
        }

        $source = $this->serializer()->denormalize($normalizedSource, PublicKeyCredentialSource::class);

        if (!$source instanceof PublicKeyCredentialSource) {
            throw new AuthException('Unable to restore the stored passkey credential.');
        }

        $validator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyFactory($context)->requestCeremony()
        );
        $updated = $validator->check(
            $source,
            $publicKeyCredential->response,
            $options,
            (string) ($context['rp_id'] ?? 'localhost'),
            $source->userHandle
        );

        return [
            'credentialId' => $this->extractCredentialId($credential),
            'source' => $this->serializer()->normalize($updated),
            'counter' => (int) $updated->counter,
        ];
    }

    public function extractCredentialId(array $credential): string
    {
        $id = $credential['id'] ?? $credential['rawId'] ?? '';

        return $this->isString($id) ? $this->trimString($id) : '';
    }

    private function serializer(): \Symfony\Component\Serializer\SerializerInterface
    {
        if ($this->serializer instanceof \Symfony\Component\Serializer\SerializerInterface) {
            return $this->serializer;
        }

        $supports = AttestationStatementSupportManager::create([
            new NoneAttestationStatementSupport(),
        ]);
        $this->serializer = (new WebauthnSerializerFactory($supports))->create();

        return $this->serializer;
    }

    /**
     * @param array<int, array<string, mixed>> $credentials
     * @return array<int, PublicKeyCredentialDescriptor>
     */
    private function credentialDescriptors(array $credentials): array
    {
        return array_values(array_filter(array_map(function (array $credential): ?PublicKeyCredentialDescriptor {
            $id = $credential['id'] ?? $credential['credential_id'] ?? '';

            if (!$this->isString($id) || $this->trimString($id) === '') {
                return null;
            }

            return PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $this->trimString($id),
                array_values(array_map('strval', (array) ($credential['transports'] ?? [])))
            );
        }, $credentials)));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function ceremonyFactory(array $context): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins(
            array_values(array_map('strval', (array) ($context['allowed_origins'] ?? []))),
            (bool) ($context['allow_subdomains'] ?? false)
        );
        $factory->setSecuredRelyingPartyId([(string) ($context['rp_id'] ?? 'localhost')]);

        return $factory;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return $this->isString($value) && $this->trimString($value) !== ''
            ? $this->trimString($value)
            : null;
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
