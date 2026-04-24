<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderEntitlement extends Model
{
    protected string $table = 'order_entitlements';

    protected array $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'product_id',
        'type',
        'status',
        'label',
        'access_key',
        'access_url',
        'download_limit',
        'downloads_used',
        'starts_at',
        'expires_at',
        'last_accessed_at',
        'metadata',
        'created_at',
        'updated_at',
    ];
}
