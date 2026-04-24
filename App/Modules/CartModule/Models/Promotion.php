<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Models;

use App\Abstracts\Database\Model;

class Promotion extends Model
{
    protected string $table = 'promotions';

    protected array $fillable = [
        'code',
        'label',
        'description',
        'type',
        'applies_to',
        'active',
        'rate_bps',
        'amount_minor',
        'shipping_rate_minor',
        'min_subtotal_minor',
        'max_subtotal_minor',
        'max_discount_minor',
        'min_items',
        'max_items',
        'usage_limit',
        'usage_count',
        'starts_at',
        'ends_at',
        'criteria',
        'source',
        'created_at',
        'updated_at',
    ];
}
