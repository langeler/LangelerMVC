<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\OtpManagerInterface;
use App\Core\Config;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class OtpManager implements OtpManagerInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function generateSecret(int $bytes = 20): string
    {
        return Base32::encodeUpper(random_bytes($bytes));
    }

    public function provision(string $label, ?string $issuer = null, ?string $secret = null): array
    {
        $secret ??= $this->generateSecret();
        $issuer ??= (string) $this->config->get('app', 'NAME', 'LangelerMVC');
        $digits = (int) $this->config->get('auth', 'OTP.DIGITS', 6);
        $period = (int) $this->config->get('auth', 'OTP.PERIOD', 30);
        $algorithm = (string) $this->config->get('auth', 'OTP.ALGORITHM', 'sha1');

        $totp = TOTP::create($secret, $period, $algorithm, $digits);
        $totp->setLabel($label);
        $totp->setIssuer($issuer);

        return [
            'secret' => $secret,
            'uri' => $totp->getProvisioningUri(),
            'issuer' => $issuer,
            'label' => $label,
            'digits' => $digits,
            'period' => $period,
        ];
    }

    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $digits = (int) $this->config->get('auth', 'OTP.DIGITS', 6);
        $period = (int) $this->config->get('auth', 'OTP.PERIOD', 30);
        $algorithm = (string) $this->config->get('auth', 'OTP.ALGORITHM', 'sha1');

        $totp = TOTP::create($secret, $period, $algorithm, $digits);

        return $totp->verify($code, $timestamp, $window);
    }

    public function recoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }
}
