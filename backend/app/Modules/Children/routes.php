<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Children\Http\Controllers\ChildController;

Route::prefix('children')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ChildController::class, 'index']);
    Route::post('/', [ChildController::class, 'store']);
    Route::get('{id}', [ChildController::class, 'show']);
    Route::put('{id}', [ChildController::class, 'update']);
    Route::delete('{id}', [ChildController::class, 'destroy']);
});