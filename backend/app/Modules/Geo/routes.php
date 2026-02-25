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

Route::middleware(["auth:sanctum", "can_access_admin_panel"])
    ->prefix("api/admin/geo")
    ->group(function (): void {
        Route::get("/countries", [AdminCountryController::class, "index"]);
        Route::post("/countries", [AdminCountryController::class, "store"]);
        Route::get("/countries/{id}", [AdminCountryController::class, "show"]);
        Route::patch("/countries/{id}", [AdminCountryController::class, "update"]);
        Route::delete("/countries/{id}", [AdminCountryController::class, "destroy"]);

        Route::get("/regions", [AdminRegionController::class, "index"]);
        Route::post("/regions", [AdminRegionController::class, "store"]);
        Route::get("/regions/{id}", [AdminRegionController::class, "show"]);
        Route::patch("/regions/{id}", [AdminRegionController::class, "update"]);
        Route::delete("/regions/{id}", [AdminRegionController::class, "destroy"]);

        Route::get("/cities", [AdminCityController::class, "index"]);
        Route::post("/cities", [AdminCityController::class, "store"]);
        Route::get("/cities/{id}", [AdminCityController::class, "show"]);
        Route::patch("/cities/{id}", [AdminCityController::class, "update"]);
        Route::delete("/cities/{id}", [AdminCityController::class, "destroy"]);

        Route::get("/districts", [AdminDistrictController::class, "index"]);
        Route::post("/districts", [AdminDistrictController::class, "store"]);
        Route::get("/districts/{id}", [AdminDistrictController::class, "show"]);
        Route::patch("/districts/{id}", [AdminDistrictController::class, "update"]);
        Route::delete("/districts/{id}", [AdminDistrictController::class, "destroy"]);
    });
