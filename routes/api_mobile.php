<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;

Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas para usuarios móviles (residentes)
Route::middleware(['auth:api', 'mobile', 'not.banned'])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
    // Aquí irán las demás rutas como Mis Pagos, Abrir Puerta, etc.
});
