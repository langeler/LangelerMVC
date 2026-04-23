<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;

class AddOrderDiscountSnapshotColumns extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddOrderCommerceStateColumns::class,
        ];
    }

    public function up(): void
    {
        $this->addColumn('orders', 'discount_code', 'VARCHAR(64)', ['nullable' => true]);
        $this->addColumn('orders', 'discount_label', 'VARCHAR(191)', ['nullable' => true]);
        $this->addColumn('orders', 'discount_snapshot', 'JSON', ['nullable' => true]);
    }

    public function down(): void
    {
        $this->dropColumn('orders', 'discount_snapshot');
        $this->dropColumn('orders', 'discount_label');
        $this->dropColumn('orders', 'discount_code');
    }
}
