<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Models;

use App\Abstracts\Database\Model;

class Cart extends Model
{
    protected string $table = 'carts';

    protected array $fillable = [
        'user_id',
        'session_key',
        'status',
        'currency',
        'created_at',
        'updated_at',
    ];
}
