<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1,api-login');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});
