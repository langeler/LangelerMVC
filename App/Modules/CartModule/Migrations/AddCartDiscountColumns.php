<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Migrations;

use App\Abstracts\Database\Migration;

class AddCartDiscountColumns extends Migration
{
    public static function dependencies(): array
    {
        return [
            CreateCartTables::class,
        ];
    }

    public function up(): void
    {
        $this->addColumn('carts', 'discount_code', 'VARCHAR(64)', ['nullable' => true]);
        $this->addColumn('carts', 'discount_label', 'VARCHAR(191)', ['nullable' => true]);
        $this->addColumn('carts', 'discount_snapshot', 'JSON', ['nullable' => true]);
    }

    public function down(): void
    {
        $this->dropColumn('carts', 'discount_snapshot');
        $this->dropColumn('carts', 'discount_label');
        $this->dropColumn('carts', 'discount_code');
    }
}
