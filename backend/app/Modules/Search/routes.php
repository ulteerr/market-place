<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;

Route::prefix("api/search")->group(function (): void {
    Route::get("/", [SearchController::class, "index"]);
    Route::get("/suggest", [SearchController::class, "suggest"]);
});
