<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Migrations;

use App\Abstracts\Database\Migration;

class AddProductFulfillmentColumns extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreateShopTables::class,
        ];
    }

    public function up(): void
    {
        $this->addColumn('products', 'fulfillment_type', 'VARCHAR(40)', ['default' => 'physical_shipping']);
        $this->addColumn('products', 'fulfillment_policy', 'JSON', ['nullable' => true]);
        $this->addColumn('products', 'available_at', 'TIMESTAMP', ['nullable' => true]);
    }

    public function down(): void
    {
        $this->dropColumn('products', 'available_at');
        $this->dropColumn('products', 'fulfillment_policy');
        $this->dropColumn('products', 'fulfillment_type');
    }
}
