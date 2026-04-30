<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class BudbeeCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'budbee';
    }

    protected function defaultLabel(): string
    {
        return 'Budbee';
    }

    protected function defaultRegions(): array
    {
        return ['SE', 'NORDIC'];
    }

    protected function defaultServiceLevels(): array
    {
        return ['locker', 'home'];
    }
}
