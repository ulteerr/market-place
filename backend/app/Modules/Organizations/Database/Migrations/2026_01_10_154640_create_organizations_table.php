<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organizations", function (Blueprint $table): void {
            $table->uuid("id")->primary();

            $table->string("name");
            $table->text("description")->nullable();

            $table->string("phone")->nullable();
            $table->string("email")->nullable();
            $table->string("status")->default("active");
            $table->string("source_type")->default("manual");
            $table->string("ownership_status")->default("unclaimed");

            $table->uuid("user_id")->nullable();
            $table->uuid("owner_user_id")->nullable();
            $table->uuid("created_by_user_id")->nullable();
            $table->timestamp("claimed_at")->nullable();

            $table->timestamps();

            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("owner_user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("created_by_user_id")->references("id")->on("users")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organizations");
    }
};
