<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Models;

use App\Abstracts\Database\Model;

class UserAuthToken extends Model
{
    protected string $table = 'user_auth_tokens';

    protected array $fillable = [
        'user_id',
        'type',
        'token_hash',
        'payload',
        'expires_at',
        'used_at',
        'created_at',
        'updated_at',
    ];
}
