<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreateShopTables extends Migration
{
    public function up(): void
    {
        $this->createTable('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191);
            $table->text('description', true);
            $table->boolean('is_published', false, true);
            $table->timestamps();
            $table->unique('slug');
        });

        $this->createTable('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id');
            $table->string('name', 191);
            $table->string('slug', 191);
            $table->text('description', true);
            $table->integer('price_minor', false, 0);
            $table->string('currency', 12, false, false, 'SEK');
            $table->string('visibility', 32, false, false, 'published');
            $table->json('media', true);
            $table->integer('stock', false, 0);
            $table->timestamps();
            $table->unique('slug');
            $table->index(['category_id', 'visibility']);
            $table->foreign('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('products');
        $this->dropTable('categories');
    }
}
