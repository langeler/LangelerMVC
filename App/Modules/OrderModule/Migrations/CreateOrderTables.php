<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateOrderTables extends Migration
{
    public static function dependencies(): array
    {
        return [
            \App\Modules\UserModule\Migrations\CreateUserPlatformTables::class,
            \App\Modules\CartModule\Migrations\CreateCartTables::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id', true);
            $table->foreignId('cart_id', true);
            $table->string('order_number', 64);
            $table->string('contact_name', 191);
            $table->string('contact_email', 191);
            $table->string('status', 32, false, false, 'placed');
            $table->string('payment_status', 32, false, false, 'authorized');
            $table->string('payment_driver', 64, false, false, 'testing');
            $table->string('payment_method', 32, false, false, 'card');
            $table->string('payment_flow', 32, false, false, 'authorize_capture');
            $table->string('payment_reference', 191, true);
            $table->string('payment_provider_reference', 191, true);
            $table->string('payment_external_reference', 191, true);
            $table->string('payment_webhook_reference', 191, true);
            $table->string('payment_idempotency_key', 191, true);
            $table->boolean('payment_customer_action_required', false, false, false);
            $table->string('currency', 12, false, false, 'SEK');
            $table->integer('subtotal_minor', false, 0);
            $table->integer('total_minor', false, 0);
            $table->json('payment_next_action', true);
            $table->json('payment_intent');
            $table->timestamps();
            $table->unique('order_number');
            $table->unique('payment_idempotency_key');
            $table->index(['user_id', 'status']);
            $table->foreign('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $table->foreign('cart_id', 'carts', 'id', 'SET NULL', 'CASCADE');
        });

        $this->createTable('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id', true);
            $table->string('product_name', 191);
            $table->integer('quantity', false, 1);
            $table->integer('unit_price_minor', false, 0);
            $table->integer('line_total_minor', false, 0);
            $table->json('metadata', true);
            $table->timestamps();
            $table->index(['order_id', 'product_id']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        });

        $this->createTable('order_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('type', 32);
            $table->string('name', 191);
            $table->string('line_one', 191);
            $table->string('line_two', 191, true);
            $table->string('postal_code', 50);
            $table->string('city', 120);
            $table->string('country', 120);
            $table->string('email', 191);
            $table->string('phone', 80, true);
            $table->timestamps();
            $table->index(['order_id', 'type']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('order_addresses');
        $this->dropTable('order_items');
        $this->dropTable('orders');
    }
}
