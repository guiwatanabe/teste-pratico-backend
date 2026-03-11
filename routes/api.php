<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1,api-login');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(['prefix' => 'users'], function () {
    Route::get('/', [UsersController::class, 'index'])->can('viewAny', App\Models\User::class);
    Route::post('/', [UsersController::class, 'store'])->can('create', App\Models\User::class);
    Route::patch('/{user}', [UsersController::class, 'update'])->can('update', 'user');
    Route::delete('/{user}', [UsersController::class, 'delete'])->can('delete', 'user');
});
