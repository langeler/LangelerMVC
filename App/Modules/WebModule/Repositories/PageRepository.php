<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\WebModule\Models\Page;

/**
 * Default content repository used to validate the module persistence pattern.
 */
class PageRepository extends Repository
{
    protected string $modelClass = Page::class;
}
