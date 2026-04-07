<?php

namespace App\Modules\WebModule\Controllers;

class HomeController
{
    /**
     * Default framework landing action.
     *
     * @return string
     */
    public function index(): string
    {
        return 'LangelerMVC is running.';
    }

    /**
     * Fallback action for unmatched routes.
     *
     * @return string
     */
    public function notFound(): string
    {
        return 'Route not found.';
    }
}
