<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\WorkOrderProgressController;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::post('/work-orders', [WorkOrderController::class, 'store']);
    Route::get('/work-orders/{id}', [WorkOrderController::class, 'show']);
    Route::put('/work-orders/{id}', [WorkOrderController::class, 'update']);
    Route::delete('/work-orders/{id}', [WorkOrderController::class, 'destroy']);

    Route::get('/work-order-progress/{id}', [WorkOrderProgressController::class, 'index']);
});