<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AdminAccessPermissionController;
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
        Route::get("/users", [AdminUserController::class, "index"])->middleware(
            "can_permission:admin.users.read",
        );
        Route::post("/users", [AdminUserController::class, "store"])->middleware(
            "can_permission:admin.users.create",
        );
        Route::get("/users/{id}", [AdminUserController::class, "show"])->middleware(
            "can_permission:admin.users.read",
        );
        Route::patch("/users/{id}", [AdminUserController::class, "update"])->middleware(
            "can_permission:admin.users.update",
        );
        Route::delete("/users/{id}", [AdminUserController::class, "destroy"])->middleware(
            "can_permission:admin.users.delete",
        );

        Route::get("/roles", [AdminRoleController::class, "index"])->middleware(
            "can_permission:admin.roles.read",
        );
        Route::post("/roles", [AdminRoleController::class, "store"])->middleware(
            "can_permission:admin.roles.create",
        );
        Route::get("/roles/{id}", [AdminRoleController::class, "show"])->middleware(
            "can_permission:admin.roles.read",
        );
        Route::patch("/roles/{id}", [AdminRoleController::class, "update"])->middleware(
            "can_permission:admin.roles.update",
        );
        Route::delete("/roles/{id}", [AdminRoleController::class, "destroy"])->middleware(
            "can_permission:admin.roles.delete",
        );

        Route::get("/permissions", [AdminAccessPermissionController::class, "index"])->middleware(
            "can_permission:admin.roles.read,admin.users.read",
        );
    });
