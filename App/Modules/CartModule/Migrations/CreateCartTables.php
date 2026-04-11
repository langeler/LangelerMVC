<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateCartTables extends Migration
{
    public static function dependencies(): array
    {
        return [
            \App\Modules\UserModule\Migrations\CreateUserPlatformTables::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id', true);
            $table->string('session_key', 255, true);
            $table->string('status', 32, false, false, 'active');
            $table->string('currency', 12, false, false, 'SEK');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->unique('session_key');
            $table->foreign('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        });

        $this->createTable('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id');
            $table->foreignId('product_id', true);
            $table->string('product_name', 191);
            $table->integer('unit_price_minor', false, 0);
            $table->integer('quantity', false, 1);
            $table->integer('line_total_minor', false, 0);
            $table->json('metadata', true);
            $table->timestamps();
            $table->index(['cart_id', 'product_id']);
            $table->foreign('cart_id', 'carts', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('cart_items');
        $this->dropTable('carts');
    }
}
