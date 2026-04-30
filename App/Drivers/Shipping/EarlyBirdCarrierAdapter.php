<?php

declare(strict_types=1);

namespace App\Drivers\Shipping;

use App\Abstracts\Support\CarrierAdapter;

class EarlyBirdCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'earlybird';
    }

    protected function defaultLabel(): string
    {
        return 'Early Bird';
    }

    protected function defaultServiceLevels(): array
    {
        return ['mailbox'];
    }

    protected function supportsServicePoints(): bool
    {
        return false;
    }
}
