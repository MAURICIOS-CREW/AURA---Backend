<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\IncidentController;
use App\Http\Controllers\Api\Admin\IncidentCommentController;

Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas para administradores
Route::middleware(['auth:api', 'admin', 'not.banned'])->group(function () {
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
    // Aquí irán las demás rutas como CRUD de usuarios, roles, reportes, etc.
    
    Route::apiResource('incidents', IncidentController::class)->except(['store']);
    
    Route::get('incidents/{incident}/comments', [IncidentCommentController::class, 'index']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store']);
    Route::delete('incidents/{incident}/comments/{comment}', [IncidentCommentController::class, 'destroy']);
});
