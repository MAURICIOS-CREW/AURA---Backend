<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\QrController;

use App\Http\Controllers\Api\Mobile\VehicleController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/ping', function () {
    return 'pong';
});

// Rutas protegidas para usuarios móviles (residentes)
Route::middleware(['auth:api', 'mobile', 'not.banned'])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user()->load(['role', 'residences.address']);
    });
    
    Route::get('/qr/temp', [QrController::class, 'getTempQr']);

    Route::apiResource('vehicles', VehicleController::class);
});
