<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organization_users", function (Blueprint $table): void {
            $table->uuid("id")->primary();

            $table->uuid("user_id");
            $table->uuid("organization_id");
            $table->uuid("role_id");
            $table->string("role_code")->nullable();
            $table->string("status")->default("active");
            $table->uuid("invited_by_user_id")->nullable();
            $table->timestamp("joined_at")->nullable();

            $table->timestamps();

            $table->unique(["organization_id", "user_id"], "organization_users_org_user_unique");
            $table->index("role_code");
            $table->index("status");

            $table->foreign("user_id")->references("id")->on("users")->cascadeOnDelete();
            $table
                ->foreign("organization_id")
                ->references("id")
                ->on("organizations")
                ->cascadeOnDelete();
            $table->foreign("role_id")->references("id")->on("organization_roles");
            $table->foreign("invited_by_user_id")->references("id")->on("users")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organization_users");
    }
};
