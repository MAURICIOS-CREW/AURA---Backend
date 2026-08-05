<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\QrController;
use App\Http\Controllers\Api\Mobile\ProfileController;

use App\Http\Controllers\Api\Mobile\VehicleController;
use App\Http\Controllers\Api\Mobile\IncidentController;
use App\Http\Controllers\Api\Mobile\IncidentCommentController;
use App\Http\Controllers\Api\Mobile\ServiceController as MobileServiceController;
use App\Http\Controllers\Api\Mobile\ContractedServiceController as MobileContractedServiceController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/ping', function () {
    return 'pong';
});

// Rutas protegidas para usuarios móviles (residentes)
Route::middleware(['auth:api', 'mobile', 'not.banned'])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/fcm', [AuthController::class, 'updateFcmToken']);
    
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    
    Route::get('/qr/temp', [QrController::class, 'getTempQr']);

    Route::apiResource('access-codes', \App\Http\Controllers\Api\Mobile\AccessCodeController::class);
    Route::get('/access-logs', [\App\Http\Controllers\Api\Mobile\AccessCodeController::class, 'logs']);

    Route::apiResource('vehicles', VehicleController::class);
    
    Route::apiResource('incidents', IncidentController::class)->except(['destroy']);
    Route::get('incidents/{incident}/comments', [IncidentCommentController::class, 'index']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store']);

    // Módulo de Servicios para Residentes
    Route::get('services', [MobileServiceController::class, 'index']);
    Route::get('services/{service}', [MobileServiceController::class, 'show']);
    Route::post('services/{service}/contract', [MobileContractedServiceController::class, 'contract']);
    
    Route::get('contracted-services', [MobileContractedServiceController::class, 'index']);
    Route::get('contracted-services/{contractedService}', [MobileContractedServiceController::class, 'show']);
    Route::patch('contracted-services/{contractedService}/complete', [MobileContractedServiceController::class, 'markCompleted']);
});
