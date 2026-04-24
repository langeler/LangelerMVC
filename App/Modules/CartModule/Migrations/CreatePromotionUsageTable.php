<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreatePromotionUsageTable extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreatePromotionTables::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('promotion_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_id', true);
            $table->string('promotion_code', 64);
            $table->foreignId('order_id', true);
            $table->foreignId('cart_id', true);
            $table->foreignId('user_id', true);
            $table->string('currency', 12, false, false, 'SEK');
            $table->integer('discount_minor', false, 0);
            $table->integer('item_discount_minor', false, 0);
            $table->integer('shipping_discount_minor', false, 0);
            $table->string('source', 32, false, false, 'database');
            $table->json('context', true);
            $table->timestamp('created_at', true);
            $table->unique(['order_id', 'promotion_code']);
            $table->index(['promotion_code', 'created_at']);
            $table->index(['promotion_id', 'created_at']);
            $table->index(['user_id', 'promotion_code']);
        });
    }

    public function down(): void
    {
        $this->dropTable('promotion_usages');
    }
}
