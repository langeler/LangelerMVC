<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Seeds;

use App\Abstracts\Database\Seed;
use App\Core\Database;
use App\Modules\WebModule\Repositories\PageRepository;

class PageSeed extends Seed
{
    public function __construct(PageRepository $repository, Database $database)
    {
        parent::__construct($repository, $database);
    }

    public function run(): void
    {
        $this->truncate();
        $this->insertMany($this->defaultData());
    }

    public function defaultData(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'LangelerMVC is running.',
                'content' => 'The starter WebModule page is now stored in the framework database layer.',
                'is_published' => 1,
            ],
            [
                'slug' => 'not-found',
                'title' => 'Route not found.',
                'content' => 'The requested route could not be resolved by the framework router.',
                'is_published' => 1,
            ],
        ];
    }
}
