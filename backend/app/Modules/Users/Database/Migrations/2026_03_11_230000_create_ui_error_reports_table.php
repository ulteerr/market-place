<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("ui_error_reports", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->foreignUuid("user_id")->nullable()->constrained("users")->nullOnDelete();
            $table->string("status")->default("received");
            $table->string("page_url", 2000)->nullable();
            $table->string("route_name")->nullable();
            $table->string("block_id");
            $table->text("description");
            $table->json("attachments")->nullable();
            $table->json("payload");
            $table->timestamp("processed_at")->nullable();
            $table->timestamps();

            $table->index(["status", "created_at"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ui_error_reports");
    }
};
