<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface PasskeyManagerInterface
{
    public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;

    /**
     * @param array<int, array<string, mixed>> $excludeCredentials
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function beginRegistration(
        int $userId,
        string $userName,
        string $displayName,
        array $excludeCredentials = [],
        array $context = []
    ): array;

    /**
     * @param array<string, mixed>|string $credentialPayload
     * @return array<string, mixed>
     */
    public function finishRegistration(array|string $credentialPayload): array;

    /**
     * @param array<int, array<string, mixed>> $allowedCredentials
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function beginAuthentication(array $allowedCredentials = [], array $context = []): array;

    /**
     * @param array<string, mixed>|string $credentialPayload
     * @param array<string, mixed> $storedCredential
     * @return array<string, mixed>
     */
    public function finishAuthentication(array|string $credentialPayload, array $storedCredential): array;

    /**
     * @param array<string, mixed>|string $credentialPayload
     */
    public function extractCredentialId(array|string $credentialPayload): string;

    public function clearPending(?string $flow = null): void;
}
