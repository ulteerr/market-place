<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organization_locations", function (Blueprint $table): void {
            $table->uuid("id")->primary();

            $table->uuid("organization_id");

            $table->uuid("country_id")->nullable();
            $table->uuid("region_id")->nullable();
            $table->uuid("city_id")->nullable();
            $table->uuid("district_id")->nullable();

            $table->string("address")->nullable();
            $table->float("lat")->nullable();
            $table->float("lng")->nullable();

            $table->timestamps();

            $table
                ->foreign("organization_id")
                ->references("id")
                ->on("organizations")
                ->cascadeOnDelete();
            $table->foreign("country_id")->references("id")->on("countries")->nullOnDelete();
            $table->foreign("region_id")->references("id")->on("regions")->nullOnDelete();
            $table->foreign("city_id")->references("id")->on("cities")->nullOnDelete();
            $table->foreign("district_id")->references("id")->on("districts")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organization_locations");
    }
};
