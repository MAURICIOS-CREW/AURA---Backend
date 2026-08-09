<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\IncidentController;
use App\Http\Controllers\Api\Admin\IncidentCommentController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\ContractedServiceController as AdminContractedServiceController;
use App\Http\Controllers\Api\Admin\AccessLogController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ExpenseController;
use App\Http\Controllers\Api\Admin\FinanceController;
use App\Http\Controllers\Api\Admin\PaymentController;

Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas para administradores
Route::middleware(['auth:api', 'admin', 'not.banned'])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
    // Aquí irán las demás rutas como CRUD de usuarios, roles, reportes, etc.

    // Generación de Reportes: el PDF se renderiza en el momento (nunca se
    // guarda en el servidor) y se devuelve como archivo para que el
    // frontend lo reciba como blob antes de abrir la pestaña nueva.
    Route::get('reports/{module}/view', [ReportController::class, 'view'])
        ->where('module', 'dashboard|finance|services');

    // Modulo de Incidentes
    Route::apiResource('incidents', IncidentController::class);

    Route::get('incidents/{incident}', [IncidentController::class, 'show']);
    Route::get('incidents', [IncidentController::class, 'index']);
    Route::post('incidents', [IncidentController::class, 'store']);

    Route::get('incidents/{incident}/comments', [IncidentCommentController::class, 'index']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store']);
    Route::delete('incidents/{incident}/comments/{comment}', [IncidentCommentController::class, 'destroy']);

    // Módulo de Servicios
    Route::apiResource('services', AdminServiceController::class);
    Route::post('services/{service}/images', [AdminServiceController::class, 'uploadImages']);
    Route::delete('services/{service}/images', [AdminServiceController::class, 'deleteImage']);
    
    // Módulo de Servicios Contratados
    Route::get('contracted-services', [AdminContractedServiceController::class, 'index']);
    Route::post('contracted-services', [AdminContractedServiceController::class, 'store']);
    Route::get('contracted-services/{contractedService}', [AdminContractedServiceController::class, 'show']);
    Route::post('contracted-services/{contractedService}/schedule', [AdminContractedServiceController::class, 'schedule']);
    Route::patch('contracted-services/{contractedService}/status', [AdminContractedServiceController::class, 'updateStatus']);

    // Módulo de Finanzas
    Route::get('finance/summary', [FinanceController::class, 'summary']);
    Route::apiResource('expenses', ExpenseController::class);

    // Módulo de Pagos (aprobación de transferencias)
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
    Route::patch('payments/{payment}/approve', [PaymentController::class, 'approve']);
    Route::patch('payments/{payment}/reject', [PaymentController::class, 'reject']);

    // Módulo de Accesos
    Route::get('access-logs', [AccessLogController::class, 'index']);

    // Endpoint de prueba para notificaciones WebSockets (Reverb)
    Route::post('notifications/test-web', [\App\Http\Controllers\Api\TestNotificationController::class, 'sendTestNotification']);
    // Módulo de Residentes
    Route::get('residents', [UserController::class, 'residents']);
    Route::get('residents/{user}/residences', [UserController::class, 'residences']);

});
