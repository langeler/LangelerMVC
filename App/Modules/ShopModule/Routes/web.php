<?php

use App\Core\Router;
use App\Modules\ShopModule\Controllers\ShopController;

return static function (Router $router): void {
    $router->get('/shop', ShopController::class, 'index', ['as' => 'shop.index']);
    $router->get('/shop/products/{slug}', ShopController::class, 'show', ['as' => 'shop.product']);

    $router->get('/api/shop', ShopController::class, 'index', ['as' => 'api.shop.index']);
    $router->get('/api/shop/products/{slug}', ShopController::class, 'show', ['as' => 'api.shop.product']);
};
