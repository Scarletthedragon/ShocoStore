<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/products', [StoreController::class, 'products']);
Route::post('/products', [StoreController::class, 'storeProduct']);
Route::get('/orders', [StoreController::class, 'orders']);
Route::post('/orders', [StoreController::class, 'storeOrder']);