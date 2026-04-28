<?php

use App\Core\Router;
use App\Modules\OrderModule\Controllers\OrderController;
use App\Modules\UserModule\Middlewares\AuthenticateMiddleware;

return static function (Router $router): void {
    $authMiddleware = [[AuthenticateMiddleware::class, 'handle']];

    $router->get('/orders/checkout', OrderController::class, 'checkoutForm', ['as' => 'orders.checkout.form']);
    $router->post('/orders/checkout', OrderController::class, 'checkout', ['as' => 'orders.checkout']);
    $router->get('/orders/complete', OrderController::class, 'complete', ['as' => 'orders.complete']);
    $router->get('/orders/complete/{reference}', OrderController::class, 'complete', ['as' => 'orders.complete.reference']);
    $router->get('/orders/cancelled', OrderController::class, 'cancelled', ['as' => 'orders.cancelled']);
    $router->get('/orders/cancelled/{reference}', OrderController::class, 'cancelled', ['as' => 'orders.cancelled.reference']);
    $router->post('/orders/webhooks/payments/{driver}', OrderController::class, 'paymentWebhook', ['as' => 'orders.webhooks.payments', 'csrf' => false]);
    $router->get('/orders', OrderController::class, 'index', ['as' => 'orders.index', 'middleware' => $authMiddleware]);
    $router->get('/orders/{order:\\d+}', OrderController::class, 'show', ['as' => 'orders.show', 'middleware' => $authMiddleware]);
    $router->get('/orders/entitlements/{key}', OrderController::class, 'entitlement', ['as' => 'orders.entitlements.access']);
    $router->post('/orders/{order:\\d+}/capture', OrderController::class, 'capture', ['as' => 'orders.capture', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/cancel', OrderController::class, 'cancel', ['as' => 'orders.cancel', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/refund', OrderController::class, 'refund', ['as' => 'orders.refund', 'middleware' => $authMiddleware]);
    $router->post('/orders/{order:\\d+}/reconcile', OrderController::class, 'reconcile', ['as' => 'orders.reconcile', 'middleware' => $authMiddleware]);

    $router->get('/api/orders/complete', OrderController::class, 'complete', ['as' => 'api.orders.complete']);
    $router->get('/api/orders/complete/{reference}', OrderController::class, 'complete', ['as' => 'api.orders.complete.reference']);
    $router->get('/api/orders/cancelled', OrderController::class, 'cancelled', ['as' => 'api.orders.cancelled']);
    $router->get('/api/orders/cancelled/{reference}', OrderController::class, 'cancelled', ['as' => 'api.orders.cancelled.reference']);
    $router->post('/api/orders/checkout', OrderController::class, 'checkout', ['as' => 'api.orders.checkout']);
    $router->post('/api/orders/webhooks/payments/{driver}', OrderController::class, 'paymentWebhook', ['as' => 'api.orders.webhooks.payments', 'csrf' => false]);
    $router->get('/api/orders', OrderController::class, 'index', ['as' => 'api.orders.index', 'middleware' => $authMiddleware]);
    $router->get('/api/orders/{order:\\d+}', OrderController::class, 'show', ['as' => 'api.orders.show', 'middleware' => $authMiddleware]);
    $router->get('/api/orders/entitlements/{key}', OrderController::class, 'entitlement', ['as' => 'api.orders.entitlements.access']);
    $router->post('/api/orders/{order:\\d+}/capture', OrderController::class, 'capture', ['as' => 'api.orders.capture', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/cancel', OrderController::class, 'cancel', ['as' => 'api.orders.cancel', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/refund', OrderController::class, 'refund', ['as' => 'api.orders.refund', 'middleware' => $authMiddleware]);
    $router->post('/api/orders/{order:\\d+}/reconcile', OrderController::class, 'reconcile', ['as' => 'api.orders.reconcile', 'middleware' => $authMiddleware]);
};
