<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\AdminOrganizationController;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\OrganizationJoinRequestController;
use Modules\Organizations\Http\Controllers\OrganizationMemberController;

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

    Route::get("/api/organizations/{organizationId}/members", [
        OrganizationMemberController::class,
        "index",
    ]);
    Route::post("/api/organizations/{organizationId}/members", [
        OrganizationMemberController::class,
        "store",
    ]);
    Route::patch("/api/organizations/{organizationId}/members/{memberId}", [
        OrganizationMemberController::class,
        "update",
    ]);
    Route::delete("/api/organizations/{organizationId}/members/{memberId}", [
        OrganizationMemberController::class,
        "destroy",
    ]);
});

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
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
