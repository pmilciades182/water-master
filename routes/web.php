<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;

// Vue.js SPA Routes - Serve the app
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|auth).*$');

// API Routes for Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de OAuth
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|facebook')
    ->name('social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|facebook')
    ->name('social.callback');


// API Routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/user', [App\Http\Controllers\Api\UserController::class, 'user']);
    Route::get('/dashboard', [App\Http\Controllers\Api\UserController::class, 'dashboard']);
    
    // RBAC Routes - Role Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\RoleController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\RoleController::class, 'store']);
        Route::get('/stats', [App\Http\Controllers\Api\RoleController::class, 'stats']);
        Route::get('/available-permissions', [App\Http\Controllers\Api\RoleController::class, 'availablePermissions']);
        Route::get('/{id}', [App\Http\Controllers\Api\RoleController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\RoleController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\RoleController::class, 'destroy']);
        Route::post('/{id}/clone', [App\Http\Controllers\Api\RoleController::class, 'clone']);
        Route::post('/{id}/permissions/assign', [App\Http\Controllers\Api\RoleController::class, 'assignPermissions']);
        Route::post('/{id}/permissions/remove', [App\Http\Controllers\Api\RoleController::class, 'removePermissions']);
        Route::post('/{id}/permissions/sync', [App\Http\Controllers\Api\RoleController::class, 'syncPermissions']);
    });
    
    // RBAC Routes - Permission Management
    Route::prefix('permissions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PermissionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\PermissionController::class, 'store']);
        Route::get('/stats', [App\Http\Controllers\Api\PermissionController::class, 'stats']);
        Route::get('/grouped-by-module', [App\Http\Controllers\Api\PermissionController::class, 'groupedByModule']);
        Route::get('/available-options', [App\Http\Controllers\Api\PermissionController::class, 'availableOptions']);
        Route::post('/create-crud', [App\Http\Controllers\Api\PermissionController::class, 'createCrudPermissions']);
        Route::get('/module/{module}', [App\Http\Controllers\Api\PermissionController::class, 'byModule']);
        Route::get('/{id}', [App\Http\Controllers\Api\PermissionController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\PermissionController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\PermissionController::class, 'destroy']);
        Route::post('/{id}/assign-to-roles', [App\Http\Controllers\Api\PermissionController::class, 'assignToRoles']);
        Route::post('/{id}/remove-from-roles', [App\Http\Controllers\Api\PermissionController::class, 'removeFromRoles']);
    });
    
    // RBAC Routes - User Role Management
    Route::prefix('user-roles')->group(function () {
        Route::get('/stats', [App\Http\Controllers\Api\UserRoleController::class, 'stats']);
        Route::get('/available-roles', [App\Http\Controllers\Api\UserRoleController::class, 'availableRoles']);
        Route::get('/users/{userId}/roles', [App\Http\Controllers\Api\UserRoleController::class, 'getUserRoles']);
        Route::get('/roles/{roleId}/users', [App\Http\Controllers\Api\UserRoleController::class, 'getRoleUsers']);
        Route::post('/assign', [App\Http\Controllers\Api\UserRoleController::class, 'assignRole']);
        Route::post('/remove', [App\Http\Controllers\Api\UserRoleController::class, 'removeRole']);
        Route::post('/sync', [App\Http\Controllers\Api\UserRoleController::class, 'syncUserRoles']);
        Route::post('/bulk-assign', [App\Http\Controllers\Api\UserRoleController::class, 'bulkAssignRole']);
        Route::post('/bulk-remove', [App\Http\Controllers\Api\UserRoleController::class, 'bulkRemoveRole']);
        Route::post('/transfer', [App\Http\Controllers\Api\UserRoleController::class, 'transferRoleUsers']);
    });
    
    // Product Management Routes
    Route::prefix('products')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProductController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\ProductController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\ProductController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\ProductController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy']);
    });
    
    Route::prefix('product-categories')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProductCategoryController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\ProductCategoryController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\ProductCategoryController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\ProductCategoryController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\ProductCategoryController::class, 'destroy']);
    });
});
