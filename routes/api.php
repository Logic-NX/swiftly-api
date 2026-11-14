<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;

// RUTAS PÚBLICAS (No requieren llave)
Route::post('/login', [AuthController::class, 'login']);

// RUTAS PROTEGIDAS (Requieren Token de Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
});