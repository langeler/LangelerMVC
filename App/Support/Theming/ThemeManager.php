<?php

declare(strict_types=1);

namespace App\Support\Theming;

/**
 * Backward-compatible alias for projects that imported the pre-organization
 * theme manager path before presentation managers were centralized.
 */
class ThemeManager extends \App\Utilities\Managers\Presentation\ThemeManager
{
}
