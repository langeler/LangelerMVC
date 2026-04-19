<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\WebModule\Models\Page;
use Throwable;

/**
 * Default content repository used to validate the module persistence pattern.
 */
class PageRepository extends Repository
{
    protected string $modelClass = Page::class;

    public function tableExists(): bool
    {
        try {
            $driver = strtolower((string) $this->db->getAttribute('driverName'));
            $result = match ($driver) {
                'sqlite' => $this->db->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$this->getTable()]
                ),
                'pgsql', 'sqlsrv' => $this->db->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                    [$this->getTable()]
                ),
                'mysql' => $this->db->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                    [$this->getTable()]
                ),
                default => 1,
            };

            return $result !== false && $result !== null;
        } catch (Throwable) {
            return false;
        }
    }
}
