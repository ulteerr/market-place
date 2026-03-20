<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("activities", function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("organization_id");
            $table->uuid("location_id");

            $table->string("name");
            $table->string("slug");
            $table->string("short_description");
            $table->text("description")->nullable();
            $table->integer("min_age")->nullable();
            $table->integer("max_age")->nullable();
            $table->integer("capacity")->nullable();
            $table->decimal("price_from", 10, 2)->nullable();
            $table->decimal("price_to", 10, 2)->nullable();
            $table->string("currency", 5)->nullable();
            $table->string("status")->default("active");
            $table->boolean("is_featured")->default(false);
            $table->timestamp("published_at")->nullable();

            $table->timestamps();

            $table->index("slug");
            $table->index("organization_id");
            $table->index("location_id");
            $table->index("status");
            $table->index("published_at");
            $table->index("is_featured");

            $table
                ->foreign("organization_id")
                ->references("id")
                ->on("organizations")
                ->cascadeOnDelete();

            $table
                ->foreign("location_id")
                ->references("id")
                ->on("organization_locations")
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("activities");
    }
};
