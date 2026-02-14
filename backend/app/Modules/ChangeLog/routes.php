<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ChangeLog\Http\Controllers\ChangeLogController;

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin/changelog")
    ->group(function (): void {
        Route::get("/", [ChangeLogController::class, "index"]);
        Route::get("/{id}", [ChangeLogController::class, "show"]);
        Route::post("/{id}/rollback", [ChangeLogController::class, "rollback"]);
    });
