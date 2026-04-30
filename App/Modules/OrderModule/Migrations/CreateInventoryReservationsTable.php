<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;
use App\Modules\ShopModule\Migrations\AddProductFulfillmentColumns;

class CreateInventoryReservationsTable extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddOrderCommerceStateColumns::class,
            AddProductFulfillmentColumns::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id', true);
            $table->foreignId('cart_id', true);
            $table->foreignId('product_id');
            $table->string('reservation_key', 96);
            $table->integer('quantity', false, 1);
            $table->string('status', 32, false, false, 'reserved');
            $table->string('source', 64, false, false, 'checkout');
            $table->timestamp('expires_at', true);
            $table->timestamp('committed_at', true);
            $table->timestamp('released_at', true);
            $table->json('metadata', true);
            $table->timestamps();
            $table->index(['reservation_key']);
            $table->index(['order_id', 'status']);
            $table->index(['cart_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->foreign('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
            $table->foreign('cart_id', 'carts', 'id', 'SET NULL', 'CASCADE');
            $table->foreign('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('inventory_reservations');
    }
}
