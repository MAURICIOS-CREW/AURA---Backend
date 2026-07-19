<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Access\ValidationController;

Route::post('/validate', [ValidationController::class, 'validateAccess']);
