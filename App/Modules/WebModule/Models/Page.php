<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Models;

use App\Abstracts\Database\Model;

/**
 * Example content model for the default web module.
 */
class Page extends Model
{
    protected string $table = 'pages';

    /**
     * @var string[]
     */
    protected array $fillable = [
        'slug',
        'title',
        'content',
        'is_published',
    ];
}
