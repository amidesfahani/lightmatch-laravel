<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/users', [UserController::class, 'index']);
Route::post('/orders', [OrderController::class, 'placeOrder']);
Route::post('/wallets/add-funds', [OrderController::class, 'addFunds']);
Route::get('/orders', [OrderController::class, 'getOrders']);