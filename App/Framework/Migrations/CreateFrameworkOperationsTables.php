<?php

declare(strict_types=1);

namespace App\Framework\Migrations;

use App\Abstracts\Database\Migration;

class CreateFrameworkOperationsTables extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('framework_audit_log')) {
            $this->createTable('framework_audit_log', function ($table): void {
                $table->id();
                $table->string('category', 120);
                $table->string('event', 255);
                $table->string('severity', 30);
                $table->string('actor_type', 255, true);
                $table->string('actor_id', 255, true);
                $table->text('context');
                $table->integer('created_at');
                $table->index('category', 'framework_audit_log_category_idx');
                $table->index('created_at', 'framework_audit_log_created_at_idx');
            });
        }

        if (!$this->tableExists('framework_failed_jobs')) {
            $this->createTable('framework_failed_jobs', function ($table): void {
                $table->string('id', 64);
                $table->primary('id', 'framework_failed_jobs_pk');
                $table->string('queue', 120);
                $table->string('type', 60);
                $table->string('class', 255);
                $table->text('handler', true);
                $table->text('payload');
                $table->integer('attempts', false, 0);
                $table->text('exception');
                $table->integer('failed_at');
                $table->index('queue', 'framework_failed_jobs_queue_idx');
                $table->index('failed_at', 'framework_failed_jobs_failed_at_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->tableExists('framework_failed_jobs')) {
            $this->dropTable('framework_failed_jobs');
        }

        if ($this->tableExists('framework_audit_log')) {
            $this->dropTable('framework_audit_log');
        }
    }

    private function tableExists(string $table): bool
    {
        return match ($this->configuredDriver()) {
            'sqlite' => $this->database->fetchColumn(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            ) !== false,
            'pgsql' => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                [$table]
            ) !== false,
            'sqlsrv' => $this->database->fetchColumn(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                [$table]
            ) !== false,
            default => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            ) !== false,
        };
    }
}
