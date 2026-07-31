<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\QrController;

use App\Http\Controllers\Api\Mobile\VehicleController;
use App\Http\Controllers\Api\Mobile\IncidentController;
use App\Http\Controllers\Api\Mobile\IncidentCommentController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/ping', function () {
    return 'pong';
});

// Rutas protegidas para usuarios móviles (residentes)
Route::middleware(['auth:api', 'mobile', 'not.banned'])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/fcm', [AuthController::class, 'updateFcmToken']);
    
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user()->load(['role', 'residences.address']);
    });
    
    Route::get('/qr/temp', [QrController::class, 'getTempQr']);

    Route::apiResource('access-codes', \App\Http\Controllers\Api\Mobile\AccessCodeController::class);

    Route::apiResource('vehicles', VehicleController::class);
    
    Route::apiResource('incidents', IncidentController::class)->except(['destroy']);
    Route::get('incidents/{incident}/comments', [IncidentCommentController::class, 'index']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store']);
});
