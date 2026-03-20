<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("leads", function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("activity_id");
            $table->uuid("user_id");
            $table->uuid("child_id")->nullable();

            $table->enum("request_for_type", ["self", "child"]);
            $table->json("contact_channels");
            $table->json("contact_payload")->nullable();
            $table->text("message")->nullable();

            $table
                ->enum("status", ["new", "in_progress", "contacted", "registered", "cancelled"])
                ->default("new");

            $table->timestamps();

            $table->index("activity_id");
            $table->index("user_id");
            $table->index("status");

            $table->foreign("activity_id")->references("id")->on("activities")->cascadeOnDelete();

            $table->foreign("user_id")->references("id")->on("users");

            $table->foreign("child_id")->references("id")->on("children")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("leads");
    }
};
