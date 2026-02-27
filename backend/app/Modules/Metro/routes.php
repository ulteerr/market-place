<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Metro\Http\Controllers\AdminMetroLineController;
use Modules\Metro\Http\Controllers\AdminMetroStationController;
use Modules\Metro\Http\Controllers\MetroLinesController;
use Modules\Metro\Http\Controllers\MetroStationsController;

Route::middleware(["auth:sanctum"])
    ->prefix("api")
    ->group(function (): void {
        Route::get("/metro-lines", [MetroLinesController::class, "index"]);
        Route::get("/metro-stations", [MetroStationsController::class, "index"]);
    });

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/metro-lines", [AdminMetroLineController::class, "index"]);
        Route::post("/metro-lines", [AdminMetroLineController::class, "store"]);
        Route::get("/metro-lines/{id}", [AdminMetroLineController::class, "show"]);
        Route::patch("/metro-lines/{id}", [AdminMetroLineController::class, "update"]);
        Route::delete("/metro-lines/{id}", [AdminMetroLineController::class, "destroy"]);

        Route::get("/metro-stations", [AdminMetroStationController::class, "index"]);
        Route::post("/metro-stations", [AdminMetroStationController::class, "store"]);
        Route::get("/metro-stations/{id}", [AdminMetroStationController::class, "show"]);
        Route::patch("/metro-stations/{id}", [AdminMetroStationController::class, "update"]);
        Route::delete("/metro-stations/{id}", [AdminMetroStationController::class, "destroy"]);
    });
