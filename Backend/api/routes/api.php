<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/products', [StoreController::class, 'products']);

Route::post('/orders', [StoreController::class, 'storeOrder']);
Route::get('/coupons/validate', [AdminCouponController::class, 'validateCoupon']);
Route::middleware('web')->group(function (): void {
    Route::get('/auth/csrf', [AuthController::class, 'csrf']);
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth')->group(function (): void {
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::get('/orders', [StoreController::class, 'orders']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/checkout', [CouponController::class, 'checkout'])->middleware('throttle:10,1');
        Route::middleware('admin')->group(function (): void {
            Route::post('/products', [StoreController::class, 'storeProduct']);
            Route::put('/products/{product}', [StoreController::class, 'updateProduct']);
            Route::delete('/products/{product}', [StoreController::class, 'destroyProduct']);
        });
        Route::middleware('admin')->prefix('admin/coupons')->group(function (): void {
            Route::get('/', [AdminCouponController::class, 'index']);
            Route::post('/', [AdminCouponController::class, 'store']);
            Route::delete('/{coupon}', [AdminCouponController::class, 'revoke']);
        });
    });
});
