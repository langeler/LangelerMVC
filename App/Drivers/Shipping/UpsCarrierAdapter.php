<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class UpsCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'ups';
    }

    protected function defaultLabel(): string
    {
        return 'UPS';
    }

    protected function defaultRegions(): array
    {
        return ['SE', 'NORDIC', 'EU', 'INTL'];
    }

    protected function defaultServiceLevels(): array
    {
        return ['home', 'standard', 'express'];
    }

    protected function supportsServicePoints(): bool
    {
        return false;
    }
}
