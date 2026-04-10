<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Models;

use App\Abstracts\Database\Model;

class Role extends Model
{
    protected string $table = 'roles';

    protected array $fillable = [
        'name',
        'label',
        'description',
        'created_at',
        'updated_at',
    ];
}
