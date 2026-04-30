<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateOrderAdjustmentTables extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddOrderDiscountSnapshotColumns::class,
            AddOrderShipmentTrackingColumns::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('order_item_id', true);
            $table->foreignId('exchange_product_id', true);
            $table->string('return_number', 64);
            $table->string('type', 32, false, false, 'return');
            $table->string('status', 32, false, false, 'requested');
            $table->integer('quantity', false, 1);
            $table->integer('refund_minor', false, 0);
            $table->string('currency', 12, false, false, 'SEK');
            $table->string('reason', 191, true);
            $table->text('resolution', true);
            $table->boolean('restock', false, false, false);
            $table->json('metadata', true);
            $table->timestamp('approved_at', true);
            $table->timestamp('completed_at', true);
            $table->timestamp('rejected_at', true);
            $table->timestamps();
            $table->unique('return_number');
            $table->index(['order_id', 'status']);
            $table->index(['order_item_id']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('order_item_id', 'order_items', 'id', 'SET NULL', 'CASCADE');
        });

        $this->createTable('order_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('return_id', true);
            $table->string('document_number', 80);
            $table->string('type', 32, false, false, 'invoice');
            $table->string('status', 32, false, false, 'issued');
            $table->string('currency', 12, false, false, 'SEK');
            $table->integer('subtotal_minor', false, 0);
            $table->integer('discount_minor', false, 0);
            $table->integer('shipping_minor', false, 0);
            $table->integer('tax_minor', false, 0);
            $table->integer('total_minor', false, 0);
            $table->integer('vat_rate_bps', false, 0);
            $table->string('seller_name', 191, true);
            $table->string('seller_vat_id', 80, true);
            $table->string('billing_country', 8, true);
            $table->text('notes', true);
            $table->json('content', true);
            $table->timestamp('issued_at', true);
            $table->timestamp('voided_at', true);
            $table->timestamps();
            $table->unique('document_number');
            $table->index(['order_id', 'type']);
            $table->index(['return_id']);
            $table->foreign('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
            $table->foreign('return_id', 'order_returns', 'id', 'SET NULL', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('order_documents');
        $this->dropTable('order_returns');
    }
}
