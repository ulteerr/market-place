<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organization_clients", function (Blueprint $table): void {
            $table->uuid("id")->primary();

            $table->uuid("organization_id");
            $table->string("subject_type");
            $table->uuid("subject_id");
            $table->string("status")->default("active");
            $table->uuid("added_by_user_id")->nullable();
            $table->timestamp("joined_at")->nullable();

            $table->timestamps();

            $table->unique(
                ["organization_id", "subject_type", "subject_id"],
                "organization_clients_org_subject_unique",
            );
            $table->index("status");
            $table->index(["organization_id", "subject_type", "status"]);

            $table
                ->foreign("organization_id")
                ->references("id")
                ->on("organizations")
                ->cascadeOnDelete();
            $table->foreign("added_by_user_id")->references("id")->on("users")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organization_clients");
    }
};
