<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::prefix('api/auth')
	->middleware('api')
	->group(function () {
		Route::post('login', [AuthController::class, 'login']);
		Route::post('register', [AuthController::class, 'register']);
		Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
	});
