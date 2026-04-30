<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class AirmeeCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'airmee';
    }

    protected function defaultLabel(): string
    {
        return 'Airmee';
    }

    protected function defaultServiceLevels(): array
    {
        return ['home'];
    }

    protected function supportsServicePoints(): bool
    {
        return false;
    }
}
