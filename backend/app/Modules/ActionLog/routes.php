<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ActionLog\Http\Controllers\AdminActionLogController;

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin/action-logs")
    ->group(function (): void {
        Route::get("/", [AdminActionLogController::class, "index"]);
    });
