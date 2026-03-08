<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AdminAccessPermissionController;
use Modules\Users\Http\Controllers\AdminObservabilityController;
use Modules\Users\Http\Controllers\AdminRealtimeObservabilityEventController;
use Modules\Users\Http\Controllers\AdminRoleController;
use Modules\Users\Http\Controllers\AdminUserController;
use Modules\Users\Http\Controllers\MeController;
use Modules\Users\Http\Controllers\PresenceController;

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/api/me", MeController::class);
    Route::patch("/api/me", [MeController::class, "updateProfile"]);
    Route::patch("/api/me/settings", [MeController::class, "updateSettings"]);
    Route::patch("/api/me/password", [MeController::class, "updatePassword"]);
    Route::post("/api/me/avatar", [MeController::class, "uploadAvatar"]);
    Route::delete("/api/me/avatar", [MeController::class, "deleteAvatar"]);
    Route::post("/api/presence/heartbeat", [PresenceController::class, "heartbeat"]);
});

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin")
    ->group(function () {
        Route::get("/users", [AdminUserController::class, "index"])->middleware(
            "can_permission:admin.users.read",
        );
        Route::post("/users", [AdminUserController::class, "store"])->middleware(
            "can_permission:admin.users.create",
        );
        Route::get("/users/stats", [AdminUserController::class, "stats"])->middleware(
            "can_permission:admin.users.read",
        );
        Route::get("/users/{id}", [AdminUserController::class, "show"])
            ->middleware("can_permission:admin.users.read")
            ->whereUuid("id");
        Route::patch("/users/{id}", [AdminUserController::class, "update"])
            ->middleware("can_permission:admin.users.update")
            ->whereUuid("id");
        Route::delete("/users/{id}", [AdminUserController::class, "destroy"])
            ->middleware("can_permission:admin.users.delete")
            ->whereUuid("id");

        Route::get("/roles", [AdminRoleController::class, "index"])->middleware(
            "can_permission:admin.roles.read",
        );
        Route::post("/roles", [AdminRoleController::class, "store"])->middleware(
            "can_permission:admin.roles.create",
        );
        Route::get("/roles/{id}", [AdminRoleController::class, "show"])
            ->middleware("can_permission:admin.roles.read")
            ->whereUuid("id");
        Route::patch("/roles/{id}", [AdminRoleController::class, "update"])
            ->middleware("can_permission:admin.roles.update")
            ->whereUuid("id");
        Route::delete("/roles/{id}", [AdminRoleController::class, "destroy"])
            ->middleware("can_permission:admin.roles.delete")
            ->whereUuid("id");

        Route::get("/permissions", [AdminAccessPermissionController::class, "index"])->middleware(
            "can_permission:admin.roles.read,admin.users.read",
        );

        Route::get("/observability", AdminObservabilityController::class)->middleware(
            "can_permission:admin.monitoring.read",
        );
        Route::post(
            "/observability/realtime-event",
            AdminRealtimeObservabilityEventController::class,
        );
    });
