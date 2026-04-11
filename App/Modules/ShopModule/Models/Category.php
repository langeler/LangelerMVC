<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Models;

use App\Abstracts\Database\Model;

class Category extends Model
{
    protected string $table = 'categories';

    protected array $fillable = [
        'name',
        'slug',
        'description',
        'is_published',
        'created_at',
        'updated_at',
    ];
}
