<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class Order extends Model
{
    protected string $table = 'orders';

    protected array $fillable = [
        'user_id',
        'cart_id',
        'order_number',
        'contact_name',
        'contact_email',
        'status',
        'payment_status',
        'payment_driver',
        'payment_reference',
        'currency',
        'subtotal_minor',
        'total_minor',
        'payment_intent',
        'created_at',
        'updated_at',
    ];
}
