<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Models;

use App\Abstracts\Database\Model;

class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price_minor',
        'currency',
        'visibility',
        'media',
        'stock',
        'created_at',
        'updated_at',
    ];
}
