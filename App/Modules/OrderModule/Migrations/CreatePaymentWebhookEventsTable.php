<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreatePaymentWebhookEventsTable extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreateOrderTables::class,
        ];
    }

    public function up(): void
    {
        $this->createTable('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('driver', 64);
            $table->string('event_id', 191);
            $table->foreignId('order_id', true);
            $table->string('order_reference', 191, true);
            $table->string('event_type', 120, true);
            $table->string('payment_status', 64, true);
            $table->string('processing_status', 32, false, false, 'received');
            $table->boolean('signature_verified', false, false, false);
            $table->json('payload', true);
            $table->text('message', true);
            $table->timestamp('received_at', true);
            $table->timestamp('processed_at', true);
            $table->timestamps();
            $table->unique(['driver', 'event_id']);
            $table->index(['order_id', 'processing_status']);
            $table->index(['driver', 'processing_status']);
            $table->foreign('order_id', 'orders', 'id', 'SET NULL', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('payment_webhook_events');
    }
}
