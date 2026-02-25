<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("districts", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->uuid("city_id");
            $table->timestamps();

            $table->foreign("city_id")->references("id")->on("cities")->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("districts");
    }
};
