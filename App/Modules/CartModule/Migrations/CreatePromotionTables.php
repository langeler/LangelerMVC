<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreatePromotionTables extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddCartDiscountColumns::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64, false, true);
            $table->string('label', 191);
            $table->text('description', true);
            $table->string('type', 40, false, false, 'fixed_amount');
            $table->string('applies_to', 40, false, false, 'cart_subtotal');
            $table->boolean('active', false, true);
            $table->integer('rate_bps', false, 0);
            $table->integer('amount_minor', false, 0);
            $table->integer('shipping_rate_minor', false, 0);
            $table->integer('min_subtotal_minor', false, 0);
            $table->integer('max_subtotal_minor', false, 0);
            $table->integer('max_discount_minor', false, 0);
            $table->integer('min_items', false, 0);
            $table->integer('max_items', false, 0);
            $table->integer('usage_limit', false, 0);
            $table->integer('usage_count', false, 0);
            $table->timestamp('starts_at', true);
            $table->timestamp('ends_at', true);
            $table->json('criteria', true);
            $table->string('source', 32, false, false, 'database');
            $table->timestamps();
            $table->index(['active', 'code']);
            $table->index(['type', 'active']);
        });
    }

    public function down(): void
    {
        $this->dropTable('promotions');
    }
}
