<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderDocument extends Model
{
    protected string $table = 'order_documents';

    protected array $fillable = [
        'order_id',
        'return_id',
        'document_number',
        'type',
        'status',
        'currency',
        'subtotal_minor',
        'discount_minor',
        'shipping_minor',
        'tax_minor',
        'total_minor',
        'vat_rate_bps',
        'seller_name',
        'seller_vat_id',
        'billing_country',
        'notes',
        'content',
        'issued_at',
        'voided_at',
        'created_at',
        'updated_at',
    ];
}
