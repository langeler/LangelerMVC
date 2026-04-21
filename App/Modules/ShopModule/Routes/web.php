<?php

use App\Core\Router;
use App\Modules\ShopModule\Controllers\ShopController;

return static function (Router $router): void {
    $router->get('/shop', ShopController::class, 'index', ['as' => 'shop.index']);
    $router->get('/shop/categories/{slug}', ShopController::class, 'category', ['as' => 'shop.category']);
    $router->get('/shop/products/{slug}', ShopController::class, 'show', ['as' => 'shop.product']);

    $router->get('/api/shop', ShopController::class, 'index', ['as' => 'api.shop.index']);
    $router->get('/api/shop/categories/{slug}', ShopController::class, 'category', ['as' => 'api.shop.category']);
    $router->get('/api/shop/products/{slug}', ShopController::class, 'show', ['as' => 'api.shop.product']);
};
