<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Models;

use App\Abstracts\Database\Model;

class CartItem extends Model
{
    protected string $table = 'cart_items';

    protected array $fillable = [
        'cart_id',
        'product_id',
        'product_name',
        'unit_price_minor',
        'quantity',
        'line_total_minor',
        'metadata',
        'created_at',
        'updated_at',
    ];
}
