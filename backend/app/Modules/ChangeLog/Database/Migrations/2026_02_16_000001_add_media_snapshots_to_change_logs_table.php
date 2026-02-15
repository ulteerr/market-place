<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("change_logs", function (Blueprint $table): void {
            $table->jsonb("media_before")->nullable()->after("before");
            $table->jsonb("media_after")->nullable()->after("after");
        });
    }

    public function down(): void
    {
        Schema::table("change_logs", function (Blueprint $table): void {
            $table->dropColumn(["media_before", "media_after"]);
        });
    }
};
