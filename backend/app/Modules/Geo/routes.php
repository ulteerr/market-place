<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Geo\Http\Controllers\AdminCityController;
use Modules\Geo\Http\Controllers\AdminCountryController;
use Modules\Geo\Http\Controllers\AdminDistrictController;
use Modules\Geo\Http\Controllers\AdminRegionController;
use Modules\Geo\Http\Controllers\CitiesController;
use Modules\Geo\Http\Controllers\CountriesController;
use Modules\Geo\Http\Controllers\DistrictsController;
use Modules\Geo\Http\Controllers\RegionsController;

Route::middleware(["auth:sanctum"])
    ->prefix("api/geo")
    ->group(function (): void {
        Route::get("/countries", [CountriesController::class, "index"]);
        Route::get("/regions", [RegionsController::class, "index"]);
        Route::get("/cities", [CitiesController::class, "index"]);
        Route::get("/districts", [DistrictsController::class, "index"]);
    });

Route::middleware(["auth:sanctum", "can_permission:admin.panel.access"])
    ->prefix("api/admin/geo")
    ->group(function (): void {
        Route::get("/countries", [AdminCountryController::class, "index"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::post("/countries", [AdminCountryController::class, "store"])->middleware(
            "can_permission:admin.geo.create",
        );
        Route::get("/countries/{id}", [AdminCountryController::class, "show"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::patch("/countries/{id}", [AdminCountryController::class, "update"])->middleware(
            "can_permission:admin.geo.update",
        );
        Route::delete("/countries/{id}", [AdminCountryController::class, "destroy"])->middleware(
            "can_permission:admin.geo.delete",
        );

        Route::get("/regions", [AdminRegionController::class, "index"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::post("/regions", [AdminRegionController::class, "store"])->middleware(
            "can_permission:admin.geo.create",
        );
        Route::get("/regions/{id}", [AdminRegionController::class, "show"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::patch("/regions/{id}", [AdminRegionController::class, "update"])->middleware(
            "can_permission:admin.geo.update",
        );
        Route::delete("/regions/{id}", [AdminRegionController::class, "destroy"])->middleware(
            "can_permission:admin.geo.delete",
        );

        Route::get("/cities", [AdminCityController::class, "index"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::post("/cities", [AdminCityController::class, "store"])->middleware(
            "can_permission:admin.geo.create",
        );
        Route::get("/cities/{id}", [AdminCityController::class, "show"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::patch("/cities/{id}", [AdminCityController::class, "update"])->middleware(
            "can_permission:admin.geo.update",
        );
        Route::delete("/cities/{id}", [AdminCityController::class, "destroy"])->middleware(
            "can_permission:admin.geo.delete",
        );

        Route::get("/districts", [AdminDistrictController::class, "index"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::post("/districts", [AdminDistrictController::class, "store"])->middleware(
            "can_permission:admin.geo.create",
        );
        Route::get("/districts/{id}", [AdminDistrictController::class, "show"])->middleware(
            "can_permission:admin.geo.read",
        );
        Route::patch("/districts/{id}", [AdminDistrictController::class, "update"])->middleware(
            "can_permission:admin.geo.update",
        );
        Route::delete("/districts/{id}", [AdminDistrictController::class, "destroy"])->middleware(
            "can_permission:admin.geo.delete",
        );
    });
