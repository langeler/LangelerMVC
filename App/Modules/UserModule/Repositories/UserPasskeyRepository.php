<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\UserModule\Models\UserPasskey;
use JsonException;

class UserPasskeyRepository extends Repository
{
    protected string $modelClass = UserPasskey::class;

    /**
     * @return list<array<string, mixed>>
     */
    public function descriptorsForUser(int $userId): array
    {
        return array_values(array_map(
            fn(UserPasskey $passkey): array => [
                'id' => $passkey->credentialId(),
                'transports' => $passkey->transportsList(),
            ],
            $this->forUser($userId)
        ));
    }

    /**
     * @return list<UserPasskey>
     */
    public function forUser(int $userId): array
    {
        return array_values(array_filter(
            $this->findBy(['user_id' => $userId]),
            static fn(mixed $passkey): bool => $passkey instanceof UserPasskey
        ));
    }

    public function findByCredentialId(string $credentialId): ?UserPasskey
    {
        $passkey = $this->findOneBy(['credential_id' => $credentialId]);

        return $passkey instanceof UserPasskey ? $passkey : null;
    }

    public function storeCredential(int $userId, string $name, array $source): UserPasskey
    {
        $record = $this->findByCredentialId((string) ($source['publicKeyCredentialId'] ?? ''));
        $payload = $this->credentialPayload($userId, $name, $source);

        if ($record instanceof UserPasskey) {
            $this->updateRow((int) $record->getKey(), $payload);

            return $this->mustFind((int) $record->getKey());
        }

        $created = $this->create($payload);

        if ($created instanceof UserPasskey) {
            return $created;
        }

        $fresh = $this->findByCredentialId((string) ($payload['credential_id'] ?? ''));

        if ($fresh instanceof UserPasskey) {
            return $fresh;
        }

        throw new \RuntimeException('The passkey record could not be persisted.');
    }

    public function refreshAssertion(int $passkeyId, array $source): UserPasskey
    {
        $this->updateRow($passkeyId, [
            'source' => $this->encodeJson($source),
            'transports' => $this->encodeJson(array_values(array_map('strval', (array) ($source['transports'] ?? [])))),
            'aaguid' => (string) ($source['aaguid'] ?? ''),
            'counter' => (int) ($source['counter'] ?? 0),
            'backup_eligible' => $this->normalizeBoolean($source['backupEligible'] ?? null),
            'backup_status' => $this->normalizeBoolean($source['backupStatus'] ?? null),
            'last_used_at' => $this->freshTimestamp(),
        ]);

        return $this->mustFind($passkeyId);
    }

    public function deleteForUser(int $userId, int $passkeyId): bool
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->delete($this->getTable())
            ->where('id', '=', $passkeyId)
            ->where('user_id', '=', $userId)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']) > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allForUserData(int $userId): array
    {
        return array_values(array_map(function (UserPasskey $passkey): array {
            return [
                'id' => (int) $passkey->getKey(),
                'name' => (string) ($passkey->getAttribute('name') ?? 'Passkey'),
                'credentialId' => $passkey->credentialId(),
                'transports' => $passkey->transportsList(),
                'counter' => (int) ($passkey->getAttribute('counter') ?? 0),
                'aaguid' => (string) ($passkey->getAttribute('aaguid') ?? ''),
                'backupEligible' => $this->normalizeBoolean($passkey->getAttribute('backup_eligible')),
                'backupStatus' => $this->normalizeBoolean($passkey->getAttribute('backup_status')),
                'lastUsedAt' => $passkey->getAttribute('last_used_at'),
                'createdAt' => $passkey->getAttribute('created_at'),
            ];
        }, $this->forUser($userId)));
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function credentialPayload(int $userId, string $name, array $source): array
    {
        return [
            'user_id' => $userId,
            'name' => $name !== '' ? $name : 'Passkey',
            'credential_id' => (string) ($source['publicKeyCredentialId'] ?? ''),
            'source' => $this->encodeJson($source),
            'transports' => $this->encodeJson(array_values(array_map('strval', (array) ($source['transports'] ?? [])))),
            'aaguid' => (string) ($source['aaguid'] ?? ''),
            'counter' => (int) ($source['counter'] ?? 0),
            'backup_eligible' => $this->normalizeBoolean($source['backupEligible'] ?? null),
            'backup_status' => $this->normalizeBoolean($source['backupStatus'] ?? null),
            'last_used_at' => null,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateRow(int $passkeyId, array $attributes): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), array_merge(
                $attributes,
                ['updated_at' => $this->freshTimestamp()]
            ))
            ->where($this->getPrimaryKey(), '=', $passkeyId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }

    /**
     * @throws JsonException
     */
    private function encodeJson(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_numeric($value) => (int) $value === 1,
            is_string($value) => in_array(strtolower($value), ['1', 'true', 'yes'], true),
            default => false,
        };
    }

    private function mustFind(int $passkeyId): UserPasskey
    {
        $passkey = $this->find($passkeyId);

        if (!$passkey instanceof UserPasskey) {
            throw new \RuntimeException('The passkey record could not be reloaded.');
        }

        return $passkey;
    }
}
