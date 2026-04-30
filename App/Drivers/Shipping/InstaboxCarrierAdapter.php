<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class InstaboxCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'instabox';
    }

    protected function defaultLabel(): string
    {
        return 'Instabox';
    }

    protected function defaultServiceLevels(): array
    {
        return ['locker'];
    }
}
