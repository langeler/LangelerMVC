<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Core\Config;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class PermissionRegistry
{
    use ArrayTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var list<string>
     */
    private array $permissions = [];

    public function __construct(Config $config)
    {
        $this->register((array) $config->get('auth', 'PERMISSIONS', []));
    }

    /**
     * @param string|array<int, string> $permissions
     */
    public function register(string|array $permissions): void
    {
        $list = is_string($permissions) ? [$permissions] : $permissions;

        foreach ($list as $permission) {
            $normalized = $this->normalize((string) $permission);

            if ($normalized === '' || $this->isInArray($normalized, $this->permissions, true)) {
                continue;
            }

            $this->permissions[] = $normalized;
        }
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    public function has(string $permission): bool
    {
        return $this->isInArray($this->normalize($permission), $this->permissions, true);
    }

    private function normalize(string $permission): string
    {
        return $this->toLowerString($this->trimString($permission));
    }
}
