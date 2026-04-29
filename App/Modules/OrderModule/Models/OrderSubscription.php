<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderSubscription extends Model
{
    protected string $table = 'order_subscriptions';

    protected array $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'product_id',
        'entitlement_id',
        'latest_order_id',
        'subscription_key',
        'plan_code',
        'plan_label',
        'status',
        'interval',
        'interval_count',
        'quantity',
        'amount_minor',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'next_billing_at',
        'next_retry_at',
        'retry_count',
        'max_retries',
        'renewal_count',
        'payment_driver',
        'provider_subscription_reference',
        'provider_customer_reference',
        'cancellation_reason',
        'paused_at',
        'resumed_at',
        'cancelled_at',
        'metadata',
        'created_at',
        'updated_at',
    ];
}
