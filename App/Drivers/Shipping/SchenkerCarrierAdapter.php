<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class SchenkerCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'schenker';
    }

    protected function defaultLabel(): string
    {
        return 'Schenker';
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
