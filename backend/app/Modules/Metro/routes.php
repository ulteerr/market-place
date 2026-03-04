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

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/metro-lines", [AdminMetroLineController::class, "index"])->middleware(
            "can_permission:admin.metro.read",
        );
        Route::post("/metro-lines", [AdminMetroLineController::class, "store"])->middleware(
            "can_permission:admin.metro.create",
        );
        Route::get("/metro-lines/{id}", [AdminMetroLineController::class, "show"])->middleware(
            "can_permission:admin.metro.read",
        );
        Route::patch("/metro-lines/{id}", [AdminMetroLineController::class, "update"])->middleware(
            "can_permission:admin.metro.update",
        );
        Route::delete("/metro-lines/{id}", [
            AdminMetroLineController::class,
            "destroy",
        ])->middleware("can_permission:admin.metro.delete");

        Route::get("/metro-stations", [AdminMetroStationController::class, "index"])->middleware(
            "can_permission:admin.metro.read",
        );
        Route::post("/metro-stations", [AdminMetroStationController::class, "store"])->middleware(
            "can_permission:admin.metro.create",
        );
        Route::get("/metro-stations/{id}", [
            AdminMetroStationController::class,
            "show",
        ])->middleware("can_permission:admin.metro.read");
        Route::patch("/metro-stations/{id}", [
            AdminMetroStationController::class,
            "update",
        ])->middleware("can_permission:admin.metro.update");
        Route::delete("/metro-stations/{id}", [
            AdminMetroStationController::class,
            "destroy",
        ])->middleware("can_permission:admin.metro.delete");
    });
