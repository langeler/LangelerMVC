<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateOrderSubscriptionsTable extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreateOrderEntitlementsTable::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('order_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('order_item_id', true);
            $table->foreignId('user_id', true);
            $table->foreignId('product_id', true);
            $table->foreignId('entitlement_id', true);
            $table->foreignId('latest_order_id', true);
            $table->string('subscription_key', 96);
            $table->string('plan_code', 96);
            $table->string('plan_label', 191);
            $table->string('status', 32, false, false, 'active');
            $table->string('interval', 24, false, false, 'monthly');
            $table->integer('interval_count', false, 1);
            $table->integer('quantity', false, 1);
            $table->integer('amount_minor', false, 0);
            $table->string('currency', 12, false, false, 'SEK');
            $table->timestamp('trial_ends_at', true);
            $table->timestamp('current_period_start', true);
            $table->timestamp('current_period_end', true);
            $table->timestamp('next_billing_at', true);
            $table->timestamp('next_retry_at', true);
            $table->integer('retry_count', false, 0);
            $table->integer('max_retries', false, 3);
            $table->integer('renewal_count', false, 0);
            $table->string('payment_driver', 64, false, false, 'testing');
            $table->string('provider_subscription_reference', 191, true);
            $table->string('provider_customer_reference', 191, true);
            $table->string('cancellation_reason', 191, true);
            $table->timestamp('paused_at', true);
            $table->timestamp('resumed_at', true);
            $table->timestamp('cancelled_at', true);
            $table->json('metadata', true);
            $table->timestamps();
            $table->unique('subscription_key');
            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['provider_subscription_reference', 'payment_driver']);
            $table->index(['next_billing_at', 'status']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('order_item_id', 'order_items', 'id', 'SET NULL', 'CASCADE');
            $table->foreign('entitlement_id', 'order_entitlements', 'id', 'SET NULL', 'CASCADE');
            $table->foreign('latest_order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('order_subscriptions');
    }
}
