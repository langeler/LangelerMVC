<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Utilities\Traits\Finder\FileFilterTrait;

class FileFinder extends Finder
{
    use FileFilterTrait;

    public function find(array $criteria = [], ?string $path = null): array
    {
        return $this->handle($criteria, $path);
    }
}
