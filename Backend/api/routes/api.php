<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/products', [StoreController::class, 'products']);
Route::post('/products', [StoreController::class, 'storeProduct']);

Route::post('/orders', [StoreController::class, 'storeOrder']);
Route::middleware('web')->group(function (): void {
    Route::get('/auth/csrf', [AuthController::class, 'csrf']);
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth')->group(function (): void {
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::get('/orders', [StoreController::class, 'orders']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1');
    });
});
