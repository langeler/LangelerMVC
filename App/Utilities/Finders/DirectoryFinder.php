<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Utilities\Traits\Finder\DirectoryFilterTrait;

class DirectoryFinder extends Finder
{
    use DirectoryFilterTrait;

    public function find(array $criteria = [], ?string $path = null): array
    {
        return $this->handle($criteria, $path);
    }
}
