<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Search route must be before resource route to avoid conflicts
    Route::get('auth/products/search', [ProductController::class, 'search']);
    Route::apiResource('auth/products', ProductController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
});


