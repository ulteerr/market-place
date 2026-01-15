<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\MeController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/me', MeController::class);
});
