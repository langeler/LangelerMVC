<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;

class AddOrderCommerceStateColumns extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreateOrderTables::class,
        ];
    }

    public function up(): void
    {
        $this->addColumn('orders', 'discount_minor', 'INTEGER', ['default' => 0]);
        $this->addColumn('orders', 'shipping_minor', 'INTEGER', ['default' => 0]);
        $this->addColumn('orders', 'tax_minor', 'INTEGER', ['default' => 0]);
        $this->addColumn('orders', 'fulfillment_status', 'VARCHAR(40)', ['default' => 'unfulfilled']);
        $this->addColumn('orders', 'inventory_status', 'VARCHAR(40)', ['default' => 'unreserved']);
    }

    public function down(): void
    {
        $this->dropColumn('orders', 'inventory_status');
        $this->dropColumn('orders', 'fulfillment_status');
        $this->dropColumn('orders', 'tax_minor');
        $this->dropColumn('orders', 'shipping_minor');
        $this->dropColumn('orders', 'discount_minor');
    }
}
