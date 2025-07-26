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
});
