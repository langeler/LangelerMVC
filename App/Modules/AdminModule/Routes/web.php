<?php

use App\Core\Router;
use App\Modules\AdminModule\Controllers\AdminController;
use App\Modules\AdminModule\Middlewares\AdminAccessMiddleware;

return static function (Router $router): void {
    $adminMiddleware = [[AdminAccessMiddleware::class, 'handle']];

    $router->get('/admin', AdminController::class, 'dashboard', ['as' => 'admin.dashboard', 'middleware' => $adminMiddleware]);
    $router->get('/admin/users', AdminController::class, 'users', ['as' => 'admin.users', 'middleware' => $adminMiddleware]);
    $router->post('/admin/users/{user:\\d+}/roles', AdminController::class, 'assignRoles', ['as' => 'admin.users.roles', 'middleware' => $adminMiddleware]);
    $router->get('/admin/roles', AdminController::class, 'roles', ['as' => 'admin.roles', 'middleware' => $adminMiddleware]);
    $router->post('/admin/roles/{role:\\d+}/permissions', AdminController::class, 'syncPermissions', ['as' => 'admin.roles.permissions', 'middleware' => $adminMiddleware]);
    $router->get('/admin/catalog', AdminController::class, 'catalog', ['as' => 'admin.catalog', 'middleware' => $adminMiddleware]);
    $router->post('/admin/catalog/categories', AdminController::class, 'saveCategory', ['as' => 'admin.catalog.categories.store', 'middleware' => $adminMiddleware]);
    $router->post('/admin/catalog/categories/{category:\\d+}/update', AdminController::class, 'updateCategory', ['as' => 'admin.catalog.categories.update', 'middleware' => $adminMiddleware]);
    $router->post('/admin/catalog/products', AdminController::class, 'saveProduct', ['as' => 'admin.catalog.products.store', 'middleware' => $adminMiddleware]);
    $router->post('/admin/catalog/products/{product:\\d+}/update', AdminController::class, 'updateProduct', ['as' => 'admin.catalog.products.update', 'middleware' => $adminMiddleware]);
    $router->get('/admin/carts', AdminController::class, 'carts', ['as' => 'admin.carts', 'middleware' => $adminMiddleware]);
    $router->get('/admin/orders', AdminController::class, 'orders', ['as' => 'admin.orders', 'middleware' => $adminMiddleware]);
    $router->get('/admin/orders/{order:\\d+}', AdminController::class, 'order', ['as' => 'admin.orders.show', 'middleware' => $adminMiddleware]);
    $router->post('/admin/orders/{order:\\d+}/capture', AdminController::class, 'captureOrder', ['as' => 'admin.orders.capture', 'middleware' => $adminMiddleware]);
    $router->post('/admin/orders/{order:\\d+}/cancel', AdminController::class, 'cancelOrder', ['as' => 'admin.orders.cancel', 'middleware' => $adminMiddleware]);
    $router->post('/admin/orders/{order:\\d+}/refund', AdminController::class, 'refundOrder', ['as' => 'admin.orders.refund', 'middleware' => $adminMiddleware]);
    $router->post('/admin/orders/{order:\\d+}/reconcile', AdminController::class, 'reconcileOrder', ['as' => 'admin.orders.reconcile', 'middleware' => $adminMiddleware]);
    $router->get('/admin/system', AdminController::class, 'system', ['as' => 'admin.system', 'middleware' => $adminMiddleware]);
    $router->get('/admin/operations', AdminController::class, 'operations', ['as' => 'admin.operations', 'middleware' => $adminMiddleware]);

    $router->get('/api/admin', AdminController::class, 'dashboard', ['as' => 'api.admin.dashboard', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/users', AdminController::class, 'users', ['as' => 'api.admin.users', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/users/{user:\\d+}/roles', AdminController::class, 'assignRoles', ['as' => 'api.admin.users.roles', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/roles', AdminController::class, 'roles', ['as' => 'api.admin.roles', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/roles/{role:\\d+}/permissions', AdminController::class, 'syncPermissions', ['as' => 'api.admin.roles.permissions', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/catalog', AdminController::class, 'catalog', ['as' => 'api.admin.catalog', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/catalog/categories', AdminController::class, 'saveCategory', ['as' => 'api.admin.catalog.categories.store', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/catalog/categories/{category:\\d+}/update', AdminController::class, 'updateCategory', ['as' => 'api.admin.catalog.categories.update', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/catalog/products', AdminController::class, 'saveProduct', ['as' => 'api.admin.catalog.products.store', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/catalog/products/{product:\\d+}/update', AdminController::class, 'updateProduct', ['as' => 'api.admin.catalog.products.update', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/carts', AdminController::class, 'carts', ['as' => 'api.admin.carts', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/orders', AdminController::class, 'orders', ['as' => 'api.admin.orders', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/orders/{order:\\d+}', AdminController::class, 'order', ['as' => 'api.admin.orders.show', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/orders/{order:\\d+}/capture', AdminController::class, 'captureOrder', ['as' => 'api.admin.orders.capture', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/orders/{order:\\d+}/cancel', AdminController::class, 'cancelOrder', ['as' => 'api.admin.orders.cancel', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/orders/{order:\\d+}/refund', AdminController::class, 'refundOrder', ['as' => 'api.admin.orders.refund', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/orders/{order:\\d+}/reconcile', AdminController::class, 'reconcileOrder', ['as' => 'api.admin.orders.reconcile', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/system', AdminController::class, 'system', ['as' => 'api.admin.system', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/operations', AdminController::class, 'operations', ['as' => 'api.admin.operations', 'middleware' => $adminMiddleware]);
};
