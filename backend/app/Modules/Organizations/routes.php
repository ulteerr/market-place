<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\AdminOrganizationController;
use Modules\Organizations\Http\Controllers\OrganizationClientController;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\OrganizationJoinRequestController;
use Modules\Organizations\Http\Controllers\OrganizationUserController;

Route::middleware(["auth:sanctum"])->group(function (): void {
    Route::get("/api/organizations/my", [OrganizationController::class, "my"]);
    Route::patch("/api/organizations/{organizationId}/owner/transfer", [
        OrganizationController::class,
        "transferOwnership",
    ]);

    Route::post("/api/organizations/{organizationId}/join-requests", [
        OrganizationJoinRequestController::class,
        "submit",
    ]);
    Route::get("/api/organizations/{organizationId}/join-requests", [
        OrganizationJoinRequestController::class,
        "index",
    ]);
    Route::get("/api/organizations/{organizationId}/join-requests/my", [
        OrganizationJoinRequestController::class,
        "my",
    ]);
    Route::patch("/api/organizations/{organizationId}/join-requests/{requestId}/approve", [
        OrganizationJoinRequestController::class,
        "approve",
    ]);
    Route::patch("/api/organizations/{organizationId}/join-requests/{requestId}/reject", [
        OrganizationJoinRequestController::class,
        "reject",
    ]);

    Route::get("/api/organizations/{organizationId}/users", [
        OrganizationUserController::class,
        "index",
    ]);
    Route::get("/api/organizations/{organizationId}/members", [
        OrganizationUserController::class,
        "index",
    ]);
    Route::get("/api/organizations/{organizationId}/clients", [
        OrganizationClientController::class,
        "index",
    ]);
    Route::post("/api/organizations/{organizationId}/users", [
        OrganizationUserController::class,
        "store",
    ]);
    Route::post("/api/organizations/{organizationId}/members", [
        OrganizationUserController::class,
        "store",
    ]);
    Route::patch("/api/organizations/{organizationId}/users/{memberId}", [
        OrganizationUserController::class,
        "update",
    ]);
    Route::patch("/api/organizations/{organizationId}/members/{memberId}", [
        OrganizationUserController::class,
        "update",
    ]);
    Route::delete("/api/organizations/{organizationId}/users/{memberId}", [
        OrganizationUserController::class,
        "destroy",
    ]);
    Route::delete("/api/organizations/{organizationId}/members/{memberId}", [
        OrganizationUserController::class,
        "destroy",
    ]);
});

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/organizations", [AdminOrganizationController::class, "index"])->middleware(
            "can_permission:org.company.profile.read",
        );
        Route::post("/organizations", [AdminOrganizationController::class, "store"])->middleware(
            "can_permission:org.company.profile.update",
        );
        Route::get("/organizations/{id}", [AdminOrganizationController::class, "show"])->middleware(
            "can_permission:org.company.profile.read",
        );
        Route::patch("/organizations/{id}", [
            AdminOrganizationController::class,
            "update",
        ])->middleware("can_permission:org.company.profile.update");
        Route::delete("/organizations/{id}", [
            AdminOrganizationController::class,
            "destroy",
        ])->middleware("can_permission:org.company.profile.delete");
    });
