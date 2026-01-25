<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\MeController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/me', MeController::class);
    Route::patch('/api/me', [MeController::class, 'updateProfile']);
    Route::patch('/api/me/password', [MeController::class, 'updatePassword']);
});
