<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface OtpManagerInterface
{
    public function generateSecret(int $bytes = 20): string;

    /**
     * @return array<string, mixed>
     */
    public function provision(string $label, ?string $issuer = null, ?string $secret = null): array;

    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool;

    /**
     * @return list<string>
     */
    public function recoveryCodes(int $count = 8): array;

    public function normalizeRecoveryCode(string $code): string;

    /**
     * @param list<string> $recoveryCodes
     */
    public function verifyRecoveryCode(array $recoveryCodes, string $code): bool;

    /**
     * @param list<string> $recoveryCodes
     * @return list<string>
     */
    public function consumeRecoveryCode(array $recoveryCodes, string $code): array;
}
