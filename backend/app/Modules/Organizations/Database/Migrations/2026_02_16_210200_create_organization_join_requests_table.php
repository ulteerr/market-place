<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("organization_join_requests", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->foreignUuid("organization_id")->constrained("organizations")->cascadeOnDelete();
            $table->foreignUuid("user_id")->constrained("users")->cascadeOnDelete();
            $table->string("status")->default("pending");
            $table->text("message")->nullable();
            $table->text("review_note")->nullable();
            $table
                ->foreignUuid("reviewed_by_user_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->timestamp("reviewed_at")->nullable();
            $table->timestamps();

            $table->index(["organization_id", "status"]);
            $table->index(["user_id", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("organization_join_requests");
    }
};
