<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Access\ValidationController;

Route::post('/qr', [ValidationController::class, 'validateAccess']);
Route::post('/plate', [ValidationController::class, 'validatePlate']);

