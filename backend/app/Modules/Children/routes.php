<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Children\Http\Controllers\AdminChildController;
use Modules\Children\Http\Controllers\ChildController;

Route::prefix("children")
    ->middleware("auth:sanctum")
    ->group(function () {
        Route::get("/", [ChildController::class, "index"]);
        Route::post("/", [ChildController::class, "store"]);
        Route::get("{id}", [ChildController::class, "show"]);
        Route::put("{id}", [ChildController::class, "update"]);
        Route::delete("{id}", [ChildController::class, "destroy"]);
    });

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/children", [AdminChildController::class, "index"])->middleware(
            "can_permission:org.children.read",
        );
        Route::post("/children", [AdminChildController::class, "store"])->middleware(
            "can_permission:org.children.write",
        );
        Route::get("/children/{id}", [AdminChildController::class, "show"])->middleware(
            "can_permission:org.children.read",
        );
        Route::patch("/children/{id}", [AdminChildController::class, "update"])->middleware(
            "can_permission:org.children.write",
        );
        Route::delete("/children/{id}", [AdminChildController::class, "destroy"])->middleware(
            "can_permission:org.children.write",
        );
    });
