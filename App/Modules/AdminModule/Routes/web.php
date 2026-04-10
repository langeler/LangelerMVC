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
    $router->get('/admin/system', AdminController::class, 'system', ['as' => 'admin.system', 'middleware' => $adminMiddleware]);

    $router->get('/api/admin', AdminController::class, 'dashboard', ['as' => 'api.admin.dashboard', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/users', AdminController::class, 'users', ['as' => 'api.admin.users', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/users/{user:\\d+}/roles', AdminController::class, 'assignRoles', ['as' => 'api.admin.users.roles', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/roles', AdminController::class, 'roles', ['as' => 'api.admin.roles', 'middleware' => $adminMiddleware]);
    $router->post('/api/admin/roles/{role:\\d+}/permissions', AdminController::class, 'syncPermissions', ['as' => 'api.admin.roles.permissions', 'middleware' => $adminMiddleware]);
    $router->get('/api/admin/system', AdminController::class, 'system', ['as' => 'api.admin.system', 'middleware' => $adminMiddleware]);
};
