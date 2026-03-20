<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("categories", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("slug");
            $table->uuid("parent_id")->nullable();
            $table->unsignedInteger("sort_order")->default(0);
            $table->boolean("is_active")->default(true);
            $table->timestamps();

            $table->unique(["parent_id", "slug"]);
            $table->index("parent_id");
            $table->index("is_active");
            $table->index(["parent_id", "sort_order"]);
        });

        Schema::table("categories", function (Blueprint $table) {
            $table->foreign("parent_id")->references("id")->on("categories")->restrictOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ["pgsql", "sqlite"], true)) {
            DB::statement(
                "CREATE UNIQUE INDEX categories_root_slug_unique ON categories (slug) WHERE parent_id IS NULL",
            );
        }
    }

    public function down(): void
    {
        Schema::table("categories", function (Blueprint $table) {
            $table->dropForeign(["parent_id"]);
        });

        Schema::dropIfExists("categories");
    }
};
