<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Purchase - role: user
    Route::post('/purchase', [PurchaseController::class, 'store'])
        ->middleware('role:user');

    // Products CRUD - role: manager, finance
    Route::apiResource('products', ProductController::class)
        ->middleware('role:manager,finance');

    // Users CRUD - role: manager
    Route::apiResource('users', UserController::class)
        ->middleware('role:manager');

    // Gateways - role: admin only (middleware already checks admin)
    Route::prefix('gateways')->middleware('role:admin')->group(function () {
        Route::get('/', [GatewayController::class, 'index']);
        Route::patch('/{gateway}/toggle', [GatewayController::class, 'toggle']);
        Route::patch('/{gateway}/priority', [GatewayController::class, 'updatePriority']);
    });

    // Clients - role: admin, finance
    Route::middleware('role:finance')->group(function () {
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
    });

    // Transactions - role: admin, finance
    Route::middleware('role:finance')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    });
});
