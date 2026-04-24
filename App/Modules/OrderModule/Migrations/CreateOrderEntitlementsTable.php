<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateOrderEntitlementsTable extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddOrderShipmentTrackingColumns::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('order_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('order_item_id', true);
            $table->foreignId('user_id', true);
            $table->foreignId('product_id', true);
            $table->string('type', 40);
            $table->string('status', 32, false, false, 'pending');
            $table->string('label', 191);
            $table->string('access_key', 96, false, true);
            $table->string('access_url', 255, true);
            $table->integer('download_limit', false, 0);
            $table->integer('downloads_used', false, 0);
            $table->timestamp('starts_at', true);
            $table->timestamp('expires_at', true);
            $table->timestamp('last_accessed_at', true);
            $table->json('metadata', true);
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'type']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('order_item_id', 'order_items', 'id', 'SET NULL', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('order_entitlements');
    }
}
