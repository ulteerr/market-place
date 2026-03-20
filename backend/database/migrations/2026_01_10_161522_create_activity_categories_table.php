<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("activity_categories", function (Blueprint $table) {
            $table->uuid("activity_id");
            $table->uuid("category_id");

            $table->unique("activity_id");
            $table->index("category_id");

            $table->foreign("activity_id")->references("id")->on("activities")->cascadeOnDelete();

            $table->foreign("category_id")->references("id")->on("categories")->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("activity_categories");
    }
};
