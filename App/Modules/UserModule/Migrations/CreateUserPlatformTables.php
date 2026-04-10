<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateUserPlatformTables extends Migration
{
    public function up(): void
    {
        $this->createTable('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('email', 191);
            $table->string('password', 255);
            $table->string('remember_token', 255, true);
            $table->timestamp('email_verified_at', true);
            $table->text('otp_secret', true);
            $table->text('otp_recovery_codes', true);
            $table->timestamp('otp_confirmed_at', true);
            $table->string('status', 50, false, false, 'active');
            $table->timestamps();
            $table->unique('email');
        });

        $this->createTable('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('label', 191, true);
            $table->text('description', true);
            $table->timestamps();
            $table->unique('name');
        });

        $this->createTable('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('label', 191, true);
            $table->text('description', true);
            $table->timestamps();
            $table->unique('name');
        });

        $this->createTable('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
            $table->foreign('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        });

        $this->createTable('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
            $table->foreign('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        });

        $this->createTable('user_auth_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type', 100);
            $table->text('token_hash');
            $table->text('payload', true);
            $table->timestamp('expires_at');
            $table->timestamp('used_at', true);
            $table->timestamps();
            $table->index(['user_id', 'type']);
            $table->foreign('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        });

        $this->createTable('user_passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name', 191);
            $table->string('credential_id', 255);
            $table->json('source');
            $table->json('transports', true);
            $table->string('aaguid', 64, true);
            $table->integer('counter', false, 0);
            $table->boolean('backup_eligible', true, false);
            $table->boolean('backup_status', true, false);
            $table->timestamp('last_used_at', true);
            $table->timestamps();
            $table->unique('credential_id');
            $table->index('user_id');
            $table->foreign('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('user_passkeys');
        $this->dropTable('user_auth_tokens');
        $this->dropTable('role_permissions');
        $this->dropTable('user_roles');
        $this->dropTable('permissions');
        $this->dropTable('roles');
        $this->dropTable('users');
    }
}
