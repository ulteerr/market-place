<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("change_logs", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("auditable_type");
            $table->string("auditable_id");
            $table->string("event", 20);
            $table->unsignedBigInteger("version");
            $table->jsonb("before")->nullable();
            $table->jsonb("after")->nullable();
            $table->jsonb("changed_fields")->nullable();
            $table->string("actor_type")->nullable();
            $table->uuid("actor_id")->nullable();
            $table->uuid("batch_id")->nullable();
            $table->uuid("rolled_back_from_id")->nullable();
            $table->jsonb("meta")->nullable();
            $table->timestamps();

            $table->index(
                ["auditable_type", "auditable_id", "version"],
                "change_logs_auditable_idx",
            );
            $table->index(["actor_type", "actor_id"], "change_logs_actor_idx");
            $table->index(["batch_id"], "change_logs_batch_idx");
            $table->index(["created_at"], "change_logs_created_idx");
            $table->index(["event"], "change_logs_event_idx");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("change_logs");
    }
};
