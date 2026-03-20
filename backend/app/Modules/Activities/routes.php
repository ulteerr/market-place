<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Activities\Http\Controllers\ActivitiesController;
use Modules\Activities\Http\Controllers\AdminActivityController;
use Modules\Activities\Http\Controllers\LeadController;

Route::prefix("api/activities")->group(function (): void {
    Route::get("/", [ActivitiesController::class, "index"]);
    Route::get("/feed", [ActivitiesController::class, "feed"]);
    Route::get("/featured", [ActivitiesController::class, "featured"]);
    Route::get("/{publicKey}", [ActivitiesController::class, "show"]);
});

Route::middleware(["auth:sanctum"])->group(function (): void {
    Route::post("/api/activities/{activityId}/leads", [LeadController::class, "submit"]);

    Route::get("/api/organizations/{organizationId}/activity-leads", [
        LeadController::class,
        "organizationIndex",
    ]);
    Route::patch("/api/organizations/{organizationId}/activity-leads/{leadId}/status", [
        LeadController::class,
        "organizationUpdateStatus",
    ]);
});

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/activities/slug-preview", [
            AdminActivityController::class,
            "slugPreview",
        ])->middleware("can_permission:admin.activities.read");
        Route::get("/activities", [AdminActivityController::class, "index"])->middleware(
            "can_permission:admin.activities.read",
        );
        Route::post("/activities", [AdminActivityController::class, "store"])->middleware(
            "can_permission:admin.activities.create",
        );
        Route::get("/activities/{id}", [AdminActivityController::class, "show"])->middleware(
            "can_permission:admin.activities.read",
        );
        Route::patch("/activities/{id}", [AdminActivityController::class, "update"])->middleware(
            "can_permission:admin.activities.update",
        );
        Route::delete("/activities/{id}", [AdminActivityController::class, "destroy"])->middleware(
            "can_permission:admin.activities.delete",
        );
        Route::get("/activity-leads", [LeadController::class, "adminIndex"])->middleware(
            "can_permission:admin.activity-leads.read",
        );
        Route::patch("/activity-leads/{leadId}/status", [
            LeadController::class,
            "adminUpdateStatus",
        ])->middleware("can_permission:admin.activity-leads.update");
    });
