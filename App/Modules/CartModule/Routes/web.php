<?php

use App\Core\Router;
use App\Modules\CartModule\Controllers\CartController;

return static function (Router $router): void {
    $router->get('/cart', CartController::class, 'show', ['as' => 'cart.show']);
    $router->post('/cart/items', CartController::class, 'addItem', ['as' => 'cart.items.add']);
    $router->post('/cart/items/{item:\\d+}/update', CartController::class, 'updateItem', ['as' => 'cart.items.update']);
    $router->post('/cart/items/{item:\\d+}/remove', CartController::class, 'removeItem', ['as' => 'cart.items.remove']);

    $router->get('/api/cart', CartController::class, 'show', ['as' => 'api.cart.show']);
    $router->post('/api/cart/items', CartController::class, 'addItem', ['as' => 'api.cart.items.add']);
    $router->post('/api/cart/items/{item:\\d+}/update', CartController::class, 'updateItem', ['as' => 'api.cart.items.update']);
    $router->post('/api/cart/items/{item:\\d+}/remove', CartController::class, 'removeItem', ['as' => 'api.cart.items.remove']);
};
