<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderReturn extends Model
{
    protected string $table = 'order_returns';

    protected array $fillable = [
        'order_id',
        'order_item_id',
        'exchange_product_id',
        'return_number',
        'type',
        'status',
        'quantity',
        'refund_minor',
        'currency',
        'reason',
        'resolution',
        'restock',
        'metadata',
        'approved_at',
        'completed_at',
        'rejected_at',
        'created_at',
        'updated_at',
    ];
}
