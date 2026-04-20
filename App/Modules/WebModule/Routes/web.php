<?php

use App\Core\Router;
use App\Modules\WebModule\Controllers\HomeController;

return static function (Router $router): void {
    $router->addRouteWithAlias('GET', '/', HomeController::class, 'index', 'home');
    $router->addRouteWithAlias('GET', '/pages/{slug}', HomeController::class, 'page', 'web.page');
    $router->addRouteWithAlias('GET', '/api/pages/{slug}', HomeController::class, 'page', 'api.web.page');
    $router->fallback(HomeController::class, 'notFound');
};
