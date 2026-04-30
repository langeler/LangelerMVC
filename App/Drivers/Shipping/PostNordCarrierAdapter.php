<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class PostNordCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'postnord';
    }

    protected function defaultLabel(): string
    {
        return 'PostNord';
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
