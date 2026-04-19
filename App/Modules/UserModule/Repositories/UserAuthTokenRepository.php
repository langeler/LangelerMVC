<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\UserModule\Models\UserAuthToken;

class UserAuthTokenRepository extends Repository
{
    protected string $modelClass = UserAuthToken::class;

    public function issueToken(int $userId, string $type, string $hash, string $expiresAt, ?string $payload = null): void
    {
        $this->create([
            'user_id' => $userId,
            'type' => $type,
            'token_hash' => $hash,
            'payload' => $payload,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);
    }

    public function revokeOutstanding(int $userId, string $type): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->delete($this->getTable())
            ->where('user_id', '=', $userId)
            ->where('type', '=', $type)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeTokens(int $userId, string $type): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->where('user_id', '=', $userId)
            ->where('type', '=', $type)
            ->whereNull('used_at')
            ->greaterThan('expires_at', gmdate('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->toExecutable();

        return $this->db->fetchAll($query['sql'], $query['bindings']);
    }

    public function markUsed(int $id): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), [
                'used_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ])
            ->where('id', '=', $id)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }

    public function deleteToken(int $id): bool
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->delete($this->getTable())
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']) > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeTokenPayloads(int $userId, string $type): array
    {
        return array_values(array_map(function (array $record): array {
            $payload = [];

            if (is_string($record['payload'] ?? null) && $record['payload'] !== '') {
                try {
                    $decoded = $this->fromJson((string) $record['payload'], true, 512, JSON_THROW_ON_ERROR);
                    $payload = is_array($decoded) ? $decoded : [];
                } catch (\JsonException) {
                    $payload = [];
                }
            }

            return [
                'id' => (int) ($record['id'] ?? 0),
                'type' => (string) ($record['type'] ?? ''),
                'expires_at' => (string) ($record['expires_at'] ?? ''),
                'used_at' => $record['used_at'] ?? null,
                'created_at' => (string) ($record['created_at'] ?? ''),
                'payload' => $payload,
            ];
        }, $this->activeTokens($userId, $type)));
    }
}
