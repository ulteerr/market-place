<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("model_action_logs", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->foreignUuid("user_id")->nullable()->constrained("users")->nullOnDelete();
            $table->string("event", 24)->index();
            $table->string("model_type", 191)->index();
            $table->string("model_id", 191)->index();
            $table->string("ip_address", 45)->nullable();
            $table->json("before")->nullable();
            $table->json("after")->nullable();
            $table->json("changed_fields")->nullable();
            $table->timestamp("created_at")->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("model_action_logs");
    }
};
