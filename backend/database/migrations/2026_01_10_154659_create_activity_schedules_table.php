<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("activity_schedules", function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("activity_id");

            $table->unsignedTinyInteger("day_of_week"); // 1=Monday … 7=Sunday
            $table->time("start_time");
            $table->time("end_time");

            $table->timestamps();

            $table->index("activity_id");
            $table->unique(["activity_id", "day_of_week", "start_time", "end_time"]);

            $table->foreign("activity_id")->references("id")->on("activities")->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("activity_schedules");
    }
};
