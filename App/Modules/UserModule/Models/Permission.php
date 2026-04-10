<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Models;

use App\Abstracts\Database\Model;

class Permission extends Model
{
    protected string $table = 'permissions';

    protected array $fillable = [
        'name',
        'label',
        'description',
        'created_at',
        'updated_at',
    ];
}
