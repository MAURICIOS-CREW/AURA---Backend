<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController;

Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas para administradores
Route::middleware(['auth:api', 'admin', 'not.banned'])->group(function () {
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
    // Aquí irán las demás rutas como CRUD de usuarios, roles, reportes, etc.
});
