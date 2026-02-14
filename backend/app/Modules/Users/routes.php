<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AdminRoleController;
use Modules\Users\Http\Controllers\AdminUserController;
use Modules\Users\Http\Controllers\MeController;

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/api/me", MeController::class);
    Route::get("/api/me/settings/stream", [MeController::class, "streamSettings"]);
    Route::patch("/api/me", [MeController::class, "updateProfile"]);
    Route::patch("/api/me/settings", [MeController::class, "updateSettings"]);
    Route::patch("/api/me/password", [MeController::class, "updatePassword"]);
    Route::post("/api/me/avatar", [MeController::class, "uploadAvatar"]);
    Route::delete("/api/me/avatar", [MeController::class, "deleteAvatar"]);
});

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin")
    ->group(function () {
        Route::adminCrud("/users", AdminUserController::class);
        Route::adminCrud("/roles", AdminRoleController::class);
    });
