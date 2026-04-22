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
        'shipping_country',
        'shipping_zone',
        'shipping_option',
        'shipping_option_label',
        'shipping_carrier',
        'shipping_carrier_label',
        'shipping_service',
        'shipping_service_label',
        'shipping_service_point_id',
        'shipping_service_point_name',
        'tracking_number',
        'tracking_url',
        'shipment_reference',
        'tracking_events',
        'shipped_at',
        'delivered_at',
        'fulfillment_status',
        'inventory_status',
        'payment_next_action',
        'payment_intent',
        'created_at',
        'updated_at',
    ];
}
