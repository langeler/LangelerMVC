<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Migrations;

use App\Abstracts\Database\Migration;
use App\Core\Schema\Blueprint;

class CreatePagesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 191, false, true);
            $table->string('title');
            $table->text('content', true);
            $table->boolean('is_published', false, true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('pages');
    }
}
