<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class BringCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'bring';
    }

    protected function defaultLabel(): string
    {
        return 'Bring';
    }

    protected function defaultRegions(): array
    {
        return ['SE', 'NORDIC', 'EU'];
    }

    protected function defaultServiceLevels(): array
    {
        return ['service_point', 'home'];
    }
}
