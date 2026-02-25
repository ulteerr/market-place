<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("cities", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->uuid("region_id")->nullable();
            $table->uuid("country_id")->nullable();
            $table->timestamps();

            $table->foreign("region_id")->references("id")->on("regions")->nullOnDelete();
            $table->foreign("country_id")->references("id")->on("countries")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("cities");
    }
};
