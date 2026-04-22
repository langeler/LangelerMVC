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
        'payment_method',
        'payment_flow',
        'payment_reference',
        'payment_provider_reference',
        'payment_external_reference',
        'payment_webhook_reference',
        'payment_idempotency_key',
        'payment_customer_action_required',
        'currency',
        'subtotal_minor',
        'discount_minor',
        'shipping_minor',
        'tax_minor',
        'total_minor',
        'fulfillment_status',
        'inventory_status',
        'payment_next_action',
        'payment_intent',
        'created_at',
        'updated_at',
    ];
}
