<?php

use App\Core\Router;
use App\Modules\WebModule\Controllers\HomeController;

return static function (Router $router): void {
    $router->addRouteWithAlias('GET', '/', HomeController::class, 'index', 'home');
    $router->fallback(HomeController::class, 'notFound');
};
