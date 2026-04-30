<?php

declare(strict_types=1);

namespace Tests\Framework;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class RepositoryConsistencyTest extends TestCase
{
    public function testClassBearingAppFilesFollowNamespaceAndFilenameConventions(): void
    {
        $errors = [];

        foreach ($this->appPhpFiles() as $file) {
            $relative = $this->relativePath($file);

            if ($this->isNonClassPhpSurface($relative)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            if (!preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatch)) {
                $errors[] = sprintf('%s is missing a namespace declaration.', $relative);
                continue;
            }

            if (!preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|enum|trait)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $symbolMatch)) {
                $errors[] = sprintf('%s is missing a class/interface/enum/trait declaration.', $relative);
                continue;
            }

            $expectedClass = pathinfo($relative, PATHINFO_FILENAME);
            $expectedNamespace = str_replace('/', '\\', dirname($relative));

            if ((string) $symbolMatch[1] !== $expectedClass) {
                $errors[] = sprintf('%s declares [%s] instead of [%s].', $relative, (string) $symbolMatch[1], $expectedClass);
            }

            if ((string) $namespaceMatch[1] !== $expectedNamespace) {
                $errors[] = sprintf('%s uses namespace [%s] instead of [%s].', $relative, (string) $namespaceMatch[1], $expectedNamespace);
            }
        }

        self::assertSame([], $errors);
    }

    public function testReleaseDataSqlReferencesMatchCurrentSchemaVocabulary(): void
    {
        $required = [
            'Data/Framework.sql' => ['framework_migrations', 'framework_migration_locks', 'framework_jobs', 'framework_failed_jobs', 'framework_audit_log'],
            'Data/Web.sql' => ['pages'],
            'Data/Users.sql' => ['users', 'roles', 'permissions', 'user_roles', 'role_permissions', 'user_auth_tokens', 'user_passkeys'],
            'Data/Products.sql' => ['categories', 'products'],
            'Data/Carts.sql' => ['carts', 'cart_items', 'promotions', 'promotion_usages'],
            'Data/Orders.sql' => ['orders', 'order_items', 'order_addresses', 'order_entitlements', 'order_subscriptions', 'inventory_reservations', 'order_returns', 'order_documents', 'payment_webhook_events'],
        ];
        $stale = [
            'coupons',
            'product_variations',
            'product_images',
            'shipment_tracking',
            'order_details',
            'user_details',
            'user_addresses',
            'user_security',
        ];
        $errors = [];

        foreach ($required as $file => $tables) {
            $path = $this->basePath($file);

            if (!is_file($path)) {
                $errors[] = sprintf('%s is missing.', $file);
                continue;
            }

            $contents = (string) file_get_contents($path);

            foreach ($tables as $table) {
                if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?["`\[]?' . preg_quote($table, '/') . '["`\]]?/i', $contents)) {
                    $errors[] = sprintf('%s does not define release table [%s].', $file, $table);
                }
            }

            foreach ($stale as $table) {
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?["`\[]?' . preg_quote($table, '/') . '["`\]]?/i', $contents)) {
                    $errors[] = sprintf('%s still defines stale table [%s].', $file, $table);
                }
            }
        }

        self::assertFileExists($this->basePath('Data/README.md'));
        self::assertSame([], $errors);
    }

    /**
     * @return list<string>
     */
    private function appPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->basePath('App')));

        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->getExtension() !== 'php') {
                continue;
            }

            $files[] = $entry->getPathname();
        }

        sort($files);

        return $files;
    }

    private function isNonClassPhpSurface(string $relative): bool
    {
        return str_starts_with($relative, 'App/Templates/')
            || str_ends_with($relative, '/Routes/web.php');
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace($this->basePath('') . DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR);
    }

    private function basePath(string $relative): string
    {
        return rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR) . ($relative === '' ? '' : DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR));
    }
}
