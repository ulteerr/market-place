<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\AdminCategoryController;
use Modules\Categories\Http\Controllers\CategoriesController;

Route::prefix("api/categories")->group(function (): void {
    Route::get("/roots", [CategoriesController::class, "roots"]);
    Route::get("/tree", [CategoriesController::class, "tree"]);
    Route::get("/{parentId}/children", [CategoriesController::class, "children"]);
});

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin")
    ->group(function (): void {
        Route::get("/categories/slug-preview", [
            AdminCategoryController::class,
            "slugPreview",
        ])->middleware("can_permission:admin.categories.read");
        Route::get("/categories/tree", [AdminCategoryController::class, "tree"])->middleware(
            "can_permission:admin.categories.read",
        );
        Route::get("/categories", [AdminCategoryController::class, "index"])->middleware(
            "can_permission:admin.categories.read",
        );
        Route::post("/categories", [AdminCategoryController::class, "store"])->middleware(
            "can_permission:admin.categories.create",
        );
        Route::get("/categories/{id}", [AdminCategoryController::class, "show"])->middleware(
            "can_permission:admin.categories.read",
        );
        Route::patch("/categories/{id}", [AdminCategoryController::class, "update"])->middleware(
            "can_permission:admin.categories.update",
        );
        Route::delete("/categories/{id}", [AdminCategoryController::class, "destroy"])->middleware(
            "can_permission:admin.categories.delete",
        );
    });
