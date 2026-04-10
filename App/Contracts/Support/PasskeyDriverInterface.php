<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface PasskeyDriverInterface
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function beginRegistration(array $context): array;

    /**
     * @param array<string, mixed> $credential
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function finishRegistration(array $credential, array $context): array;

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function beginAuthentication(array $context): array;

    /**
     * @param array<string, mixed> $credential
     * @param array<string, mixed> $storedCredential
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function finishAuthentication(array $credential, array $storedCredential, array $context): array;

    /**
     * @param array<string, mixed> $credential
     */
    public function extractCredentialId(array $credential): string;
}
