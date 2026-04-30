<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class DhlCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'dhl';
    }

    protected function defaultLabel(): string
    {
        return 'DHL';
    }

    protected function defaultRegions(): array
    {
        return ['SE', 'NORDIC', 'EU', 'INTL'];
    }

    protected function defaultServiceLevels(): array
    {
        return ['service_point', 'home', 'express'];
    }
}
