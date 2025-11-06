<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DependenceController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TechnicalController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\PenaltyController;

// Rutas públicas
Route::post('/users/login', [UserController::class, 'login']);
Route::post('/users/register', [UserController::class, 'register']);
Route::get('/hola', function () {
    return response()->json(['message' => '¡Hola!']);
});

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logs', [LogController::class, 'index']);

    // Usuarios
    Route::prefix('/users')->group(function () {
        Route::get('/index', [UserController::class, 'index']);
        Route::post('/logout', [UserController::class, 'logout']);
        Route::delete('/delete', [UserController::class, 'destroy']);
    });

    // Dependencias
    Route::prefix('/dependence')->group(function () {
        Route::get('/index', [DependenceController::class, 'index']);
        Route::post('/createorUpdate', [DependenceController::class, 'createorUpdate']);
        Route::delete('/delete', [DependenceController::class, 'destroy']);
    });

    // Procedimientos
  

    // Permisos
    Route::prefix('/permissions')->group(function () {
        Route::get('/index', [PermissionController::class, 'index']);
    });

    // Técnicos

    Route::prefix('penalties')->group(function () {
        Route::get('/index', [PenaltyController::class, 'index']);
        Route::post('/historial', [PenaltyController::class, 'historial']);

        Route::post('/createorUpdate', [PenaltyController::class, 'storeOrUpdate']); // ← crea o actualiza
        Route::delete('/delete', [PenaltyController::class, 'toggleActive']);
    });
});
