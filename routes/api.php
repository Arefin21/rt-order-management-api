<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Search route must be before resource route to avoid conflicts
    Route::get('auth/products/search', [ProductController::class, 'search']);
    Route::apiResource('auth/products', ProductController::class)->only(['index', 'show', 'store', 'update', 'destroy']);

    // Order routes
    Route::get('auth/orders', [OrderController::class, 'index']);
    Route::post('auth/orders', [OrderController::class, 'store']);
    Route::get('auth/orders/{id}', [OrderController::class, 'show']);
    Route::put('auth/orders/{id}', [OrderController::class, 'update']);
    Route::patch('auth/orders/{id}', [OrderController::class, 'update']);
    Route::delete('auth/orders/{id}', [OrderController::class, 'destroy']);
    Route::post('auth/orders/place', [OrderController::class, 'place']);
    Route::post('auth/orders/payment', [OrderController::class, 'fakePayment']);
});


