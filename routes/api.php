<?php

use App\Http\Controllers\AlcoholProcessController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DependenceController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TechnicalController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\CauseOfDetentionController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\PublicSecuritiesController;
use App\Http\Controllers\ReportResidencesController;
use App\Http\Controllers\ReportsCalendaryController;
use App\Http\Controllers\SeguimientoAlcoholController;
use App\Http\Controllers\SenderController;
use App\Http\Controllers\TrafficController;
use App\Models\Publicsecurities;

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
    Route::prefix('/causeOfDetention')->group(function () {
        Route::get('/index', [CauseOfDetentionController::class, 'index']);
        Route::post('/createorUpdate', [CauseOfDetentionController::class, 'createorUpdate']);
        Route::delete('/delete', [CauseOfDetentionController::class, 'destroy']);
    });
    Route::prefix('/sender')->group(function () {
        Route::get('/index', [SenderController::class, 'index']);
        Route::post('/createorUpdate', [SenderController::class, 'createorUpdate']);
        Route::delete('/delete', [SenderController::class, 'destroy']);
    });
    Route::prefix('/doctor')->group(function () {
        Route::get('/index', [DoctorController::class, 'index']);
        Route::post('/createorUpdate', [DoctorController::class, 'createorUpdate']);
        Route::delete('/delete', [DoctorController::class, 'destroy']);
    });
    Route::prefix('/court')->group(function () {
        Route::get('/index', [CourtController::class, 'index']);
        Route::post('/createorUpdate', [CourtController::class, 'createorUpdate']);
        Route::delete('/delete', [CourtController::class, 'destroy']);
    });
    // Procedimientos


    // Permisos
    Route::prefix('/permissions')->group(function () {
        Route::get('/index', [PermissionController::class, 'index']);
    });

    // Técnicos

    Route::prefix('penalties')->group(function () {
        Route::get('/index', [PenaltyController::class, 'index']);
        Route::get('/historial/{id}', [PenaltyController::class, 'historial']);
        Route::get('/courts', [PenaltyController::class, 'courts']);

        
        Route::post('/createorUpdate', [PenaltyController::class, 'storeOrUpdate']); // ← crea o actualiza
        Route::delete('/delete', [PenaltyController::class, 'toggleActive']);
    });
    Route::prefix('traffic')->group(function () {
        Route::get('/index', [TrafficController::class, 'index']);
        Route::post('/createorUpdate', [TrafficController::class, 'createorUpdate']); // ← crea o actualiza
        Route::delete('/delete', [TrafficController::class, 'destroy']);
        // Route::post('/historial', [PenaltyController::class, 'historial']);
        // Route::get('/courts', [PenaltyController::class, 'courts']);


    });
    Route::prefix('public_security')->group(function () {
        Route::get('/index', [PublicSecuritiesController::class, 'index']);
        Route::post('/createorUpdate', [PublicSecuritiesController::class, 'createorUpdate']); // ← crea o actualiza
        Route::delete('/delete', [PublicSecuritiesController::class, 'destroy']);
        // Route::post('/historial', [PenaltyController::class, 'historial']);
        // Route::get('/courts', [PenaltyController::class, 'courts']);


    });
    Route::prefix('calendary')->group(function () {
        Route::get('/index', [ReportsCalendaryController::class, 'index']);
     
        // Route::post('/historial', [PenaltyController::class, 'historial']);
        // Route::get('/courts', [PenaltyController::class, 'courts']);
    
    
    });
    Route::prefix('alcohol_cases')->group(function () {
        Route::get('/', [AlcoholProcessController::class, 'index']);
        Route::post('/createorUpdate', [AlcoholProcessController::class, 'store']);
        Route::get('/range', [AlcoholProcessController::class, 'getByAlcoholRange']);
        Route::get('/process/{processId}', [AlcoholProcessController::class, 'getByProcess']);
        Route::post('/advance', [AlcoholProcessController::class, 'advance']);
        Route::post('/show', [AlcoholProcessController::class, 'show']);

        // Route::prefix('{id}')->group(function () {
        //     Route::get('/', [AlcoholProcessController::class, 'show']);
        //     Route::put('/', [AlcoholProcessController::class, 'update']);
        //     Route::delete('/', [AlcoholProcessController::class, 'destroy']);
        //     Route::post('/confirm', [AlcoholProcessController::class, 'confirm']);
        // });
    });

    Route::prefix('reportsrecidences')->group(function () {
        Route::get('/index', [ReportResidencesController::class, 'index']);
       
        // Route::post('/historial', [PenaltyController::class, 'historial']);
        // Route::get('/courts', [PenaltyController::class, 'courts']);


    });
    Route::prefix('seguimiento')->group(function () {
        Route::post('/seguimiento', [SeguimientoAlcoholController::class, 'seguimiento']);

        // Route::post('/historial', [PenaltyController::class, 'historial']);
        // Route::get('/courts', [PenaltyController::class, 'courts']);


    });
});
Route::get('/test-log', function () {
    \Log::info('Test log message', ['data' => 'test']);

    // Verifica el archivo directamente
    $logPath = storage_path('logs/laravel.log');
    return response()->json([
        'log_file_exists' => file_exists($logPath),
        'log_file_writable' => is_writable($logPath),
        'log_file_size' => file_exists($logPath) ? filesize($logPath) : 0,
        'log_path' => $logPath
    ]);
});