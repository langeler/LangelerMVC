<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Models;

use App\Abstracts\Database\Model;

class UserPasskey extends Model
{
    protected string $table = 'user_passkeys';

    protected array $fillable = [
        'user_id',
        'name',
        'credential_id',
        'source',
        'transports',
        'aaguid',
        'counter',
        'backup_eligible',
        'backup_status',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, mixed>
     */
    public function sourceData(): array
    {
        $value = $this->getAttribute('source');

        if (is_string($value)) {
            try {
                $decoded = $this->fromJson($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = [];
            }
        } else {
            $decoded = $value;
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    public function transportsList(): array
    {
        $value = $this->getAttribute('transports');

        if (is_string($value)) {
            try {
                $decoded = $this->fromJson($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = [];
            }
        } else {
            $decoded = $value;
        }

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    public function credentialId(): string
    {
        $value = $this->getAttribute('credential_id');

        return is_string($value) ? $value : '';
    }
}
