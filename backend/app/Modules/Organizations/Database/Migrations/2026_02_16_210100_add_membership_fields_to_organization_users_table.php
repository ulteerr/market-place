<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("organization_users", function (Blueprint $table): void {
            if (!Schema::hasColumn("organization_users", "role_code")) {
                $table->string("role_code")->nullable()->after("role_id");
                $table->index("role_code");
            }

            if (!Schema::hasColumn("organization_users", "status")) {
                $table->string("status")->default("active")->after("role_code");
                $table->index("status");
            }

            if (!Schema::hasColumn("organization_users", "invited_by_user_id")) {
                $table->uuid("invited_by_user_id")->nullable()->after("status");
                $table
                    ->foreign("invited_by_user_id")
                    ->references("id")
                    ->on("users")
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn("organization_users", "joined_at")) {
                $table->timestamp("joined_at")->nullable()->after("invited_by_user_id");
            }
        });

        Schema::table("organization_users", function (Blueprint $table): void {
            $table->unique(["organization_id", "user_id"], "organization_users_org_user_unique");
        });
    }

    public function down(): void
    {
        Schema::table("organization_users", function (Blueprint $table): void {
            $table->dropUnique("organization_users_org_user_unique");

            if (Schema::hasColumn("organization_users", "joined_at")) {
                $table->dropColumn("joined_at");
            }

            if (Schema::hasColumn("organization_users", "invited_by_user_id")) {
                $table->dropForeign(["invited_by_user_id"]);
                $table->dropColumn("invited_by_user_id");
            }

            if (Schema::hasColumn("organization_users", "status")) {
                $table->dropIndex(["status"]);
                $table->dropColumn("status");
            }

            if (Schema::hasColumn("organization_users", "role_code")) {
                $table->dropIndex(["role_code"]);
                $table->dropColumn("role_code");
            }
        });
    }
};
