<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class InventoryReservation extends Model
{
    protected string $table = 'inventory_reservations';

    protected array $fillable = [
        'order_id',
        'cart_id',
        'product_id',
        'reservation_key',
        'quantity',
        'status',
        'source',
        'expires_at',
        'committed_at',
        'released_at',
        'metadata',
        'created_at',
        'updated_at',
    ];
}
