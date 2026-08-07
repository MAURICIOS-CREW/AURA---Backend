<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\IncidentController;
use App\Http\Controllers\Api\Admin\IncidentCommentController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\ContractedServiceController as AdminContractedServiceController;
use App\Http\Controllers\Api\Admin\AccessLogController;


Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas para administradores
Route::middleware(['auth:api', 'admin', 'not.banned'])->group(function () {
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
    // Aquí irán las demás rutas como CRUD de usuarios, roles, reportes, etc.
    
    // Modulo de Incidentes
    Route::apiResource('incidents', IncidentController::class)->except(['store']);

    Route::get('incidents/{incident}', [IncidentController::class, 'show']);
    Route::get('incidents', [IncidentController::class, 'index']);

    Route::get('incidents/{incident}/comments', [IncidentCommentController::class, 'index']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store']);
    Route::delete('incidents/{incident}/comments/{comment}', [IncidentCommentController::class, 'destroy']);

    // Módulo de Servicios
    Route::apiResource('services', AdminServiceController::class);
    Route::post('services/{service}/images', [AdminServiceController::class, 'uploadImages']);
    Route::delete('services/{service}/images', [AdminServiceController::class, 'deleteImage']);
    
    // Módulo de Servicios Contratados
    Route::get('contracted-services', [AdminContractedServiceController::class, 'index']);
    Route::get('contracted-services/{contractedService}', [AdminContractedServiceController::class, 'show']);
    Route::post('contracted-services/{contractedService}/schedule', [AdminContractedServiceController::class, 'schedule']);
    Route::patch('contracted-services/{contractedService}/status', [AdminContractedServiceController::class, 'updateStatus']);

    // Módulo de Accesos
    Route::get('access-logs', [AccessLogController::class, 'index']);


});
