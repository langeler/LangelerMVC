<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class PaymentWebhookEvent extends Model
{
    protected string $table = 'payment_webhook_events';

    protected array $fillable = [
        'driver',
        'event_id',
        'order_id',
        'order_reference',
        'event_type',
        'payment_status',
        'processing_status',
        'signature_verified',
        'payload',
        'message',
        'received_at',
        'processed_at',
        'created_at',
        'updated_at',
    ];
}
