<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organization_location_metro_stations", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->uuid("organization_location_id");
            $table->uuid("metro_station_id");
            $table->string("travel_mode", 16);
            $table->unsignedSmallInteger("duration_minutes");
            $table->timestamps();

            $table
                ->foreign("organization_location_id")
                ->references("id")
                ->on("organization_locations")
                ->cascadeOnDelete();
            $table
                ->foreign("metro_station_id")
                ->references("id")
                ->on("metro_stations")
                ->cascadeOnDelete();

            $table->unique(
                ["organization_location_id", "metro_station_id", "travel_mode"],
                "org_location_metro_station_mode_unique",
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organization_location_metro_stations");
    }
};
