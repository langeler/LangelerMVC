<?php

use App\Core\Router;
use App\Modules\OrderModule\Controllers\OrderController;
use App\Modules\UserModule\Middlewares\AuthenticateMiddleware;

return static function (Router $router): void {
    $authMiddleware = [[AuthenticateMiddleware::class, 'handle']];

    $router->get('/orders/checkout', OrderController::class, 'checkoutForm', ['as' => 'orders.checkout.form']);
    $router->post('/orders/checkout', OrderController::class, 'checkout', ['as' => 'orders.checkout']);
    $router->get('/orders', OrderController::class, 'index', ['as' => 'orders.index', 'middleware' => $authMiddleware]);
    $router->get('/orders/{order:\\d+}', OrderController::class, 'show', ['as' => 'orders.show', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/capture', OrderController::class, 'capture', ['as' => 'orders.capture', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/cancel', OrderController::class, 'cancel', ['as' => 'orders.cancel', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/refund', OrderController::class, 'refund', ['as' => 'orders.refund', 'middleware' => $authMiddleware]);

    $router->post('/api/orders/checkout', OrderController::class, 'checkout', ['as' => 'api.orders.checkout']);
    $router->get('/api/orders', OrderController::class, 'index', ['as' => 'api.orders.index', 'middleware' => $authMiddleware]);
    $router->get('/api/orders/{order:\\d+}', OrderController::class, 'show', ['as' => 'api.orders.show', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/capture', OrderController::class, 'capture', ['as' => 'api.orders.capture', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/cancel', OrderController::class, 'cancel', ['as' => 'api.orders.cancel', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/refund', OrderController::class, 'refund', ['as' => 'api.orders.refund', 'middleware' => $authMiddleware]);
};
