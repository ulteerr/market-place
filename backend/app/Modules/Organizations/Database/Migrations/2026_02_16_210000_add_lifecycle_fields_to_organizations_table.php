<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("organizations", function (Blueprint $table): void {
            if (!Schema::hasColumn("organizations", "status")) {
                $table->string("status")->default("active")->after("email");
            }

            if (!Schema::hasColumn("organizations", "source_type")) {
                $table->string("source_type")->default("manual")->after("status");
            }

            if (!Schema::hasColumn("organizations", "ownership_status")) {
                $table->string("ownership_status")->default("unclaimed")->after("source_type");
            }

            if (!Schema::hasColumn("organizations", "owner_user_id")) {
                $table->uuid("owner_user_id")->nullable()->after("user_id");
                $table->foreign("owner_user_id")->references("id")->on("users")->nullOnDelete();
            }

            if (!Schema::hasColumn("organizations", "created_by_user_id")) {
                $table->uuid("created_by_user_id")->nullable()->after("owner_user_id");
                $table
                    ->foreign("created_by_user_id")
                    ->references("id")
                    ->on("users")
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn("organizations", "claimed_at")) {
                $table->timestamp("claimed_at")->nullable()->after("created_by_user_id");
            }
        });
    }

    public function down(): void
    {
        Schema::table("organizations", function (Blueprint $table): void {
            if (Schema::hasColumn("organizations", "claimed_at")) {
                $table->dropColumn("claimed_at");
            }

            if (Schema::hasColumn("organizations", "created_by_user_id")) {
                $table->dropForeign(["created_by_user_id"]);
                $table->dropColumn("created_by_user_id");
            }

            if (Schema::hasColumn("organizations", "owner_user_id")) {
                $table->dropForeign(["owner_user_id"]);
                $table->dropColumn("owner_user_id");
            }

            if (Schema::hasColumn("organizations", "ownership_status")) {
                $table->dropColumn("ownership_status");
            }

            if (Schema::hasColumn("organizations", "source_type")) {
                $table->dropColumn("source_type");
            }

            if (Schema::hasColumn("organizations", "status")) {
                $table->dropColumn("status");
            }
        });
    }
};
