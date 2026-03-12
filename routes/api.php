<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

Route::post('/purchase', PurchaseController::class);

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1,api-login');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/', [UsersController::class, 'index'])->can('viewAny', App\Models\User::class);
    Route::post('/', [UsersController::class, 'store'])->can('create', App\Models\User::class);
    Route::patch('/{user}', [UsersController::class, 'update'])->can('update', 'user');
    Route::delete('/{user}', [UsersController::class, 'destroy'])->can('delete', 'user');
});

Route::middleware('auth:sanctum')->prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->can('viewAny', App\Models\Product::class);
    Route::post('/', [ProductController::class, 'store'])->can('create', App\Models\Product::class);
    Route::patch('/{product}', [ProductController::class, 'update'])->can('update', 'product');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->can('delete', 'product');
});

Route::middleware('auth:sanctum')->prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->can('viewAny', App\Models\Client::class);
    Route::get('/{client}', [ClientController::class, 'show'])->can('view', 'client');
});

Route::middleware('auth:sanctum')->prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->can('viewAny', App\Models\Transaction::class);
    Route::get('/{transaction}', [TransactionController::class, 'show'])->can('view', 'transaction');
    Route::post('/{transaction}/refund', [TransactionController::class, 'refund'])->can('refund', 'transaction');
});

Route::middleware('auth:sanctum')->prefix('gateways')->group(function () {
    Route::patch('/{gateway}', [GatewayController::class, 'update'])->can('manage', App\Models\Gateway::class);
});
