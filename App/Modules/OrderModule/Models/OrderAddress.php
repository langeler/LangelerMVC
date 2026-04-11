<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Models;

use App\Abstracts\Database\Model;

class OrderAddress extends Model
{
    protected string $table = 'order_addresses';

    protected array $fillable = [
        'order_id',
        'type',
        'name',
        'line_one',
        'line_two',
        'postal_code',
        'city',
        'country',
        'email',
        'phone',
        'created_at',
        'updated_at',
    ];
}
