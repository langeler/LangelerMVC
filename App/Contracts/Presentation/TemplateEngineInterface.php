<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

interface TemplateEngineInterface
{
    public function supports(string $templatePath): bool;

    public function resolveRenderablePath(string $templatePath): string;

    public function compileString(string $template, ?string $sourcePath = null): string;
}
