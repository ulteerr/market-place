<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("metro_lines", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("external_id")->nullable();
            $table->string("line_id")->nullable();
            $table->string("color", 32)->nullable();
            $table->uuid("city_id");
            $table->string("source");
            $table->timestamps();

            $table->foreign("city_id")->references("id")->on("cities")->cascadeOnDelete();
            $table->unique(["city_id", "name"], "metro_lines_city_name_unique");
            $table->unique(["city_id", "external_id"], "metro_lines_city_external_id_unique");
            $table->unique(["city_id", "line_id"], "metro_lines_city_line_id_unique");
        });

        Schema::create("metro_stations", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("external_id")->nullable();
            $table->string("line_id")->nullable();
            $table->decimal("geo_lat", 10, 7)->nullable();
            $table->decimal("geo_lon", 10, 7)->nullable();
            $table->boolean("is_closed")->nullable();
            $table->uuid("metro_line_id");
            $table->uuid("city_id");
            $table->string("source");
            $table->timestamps();

            $table
                ->foreign("metro_line_id")
                ->references("id")
                ->on("metro_lines")
                ->cascadeOnDelete();
            $table->foreign("city_id")->references("id")->on("cities")->cascadeOnDelete();
            $table->unique(
                ["city_id", "name", "metro_line_id"],
                "metro_stations_city_name_line_unique",
            );
            $table->unique(["city_id", "external_id"], "metro_stations_city_external_id_unique");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("metro_stations");
        Schema::dropIfExists("metro_lines");
    }
};
