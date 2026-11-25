<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\DailyRecordController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SimulationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class);

    Route::get('/batches', [BatchController::class, 'index']);
    Route::post('/batches', [BatchController::class, 'store']);
    Route::get('/batches/{batch}', [BatchController::class, 'show']);
    Route::post('/batches/{batch}/daily-records', [DailyRecordController::class, 'store']);

    Route::get('/inventory/items', [InventoryController::class, 'index']);
    Route::post('/inventory/items/{item}/purchase', [InventoryController::class, 'purchase']);

    Route::get('/finance/transactions', [FinanceController::class, 'index']);
    Route::post('/finance/transactions', [FinanceController::class, 'store']);

    Route::post('/simulations/calculate', [SimulationController::class, 'calculate']);
});
