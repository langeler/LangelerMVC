<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderItem extends Model
{
    protected string $table = 'order_items';

    protected array $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price_minor',
        'line_total_minor',
        'metadata',
        'created_at',
        'updated_at',
    ];
}
