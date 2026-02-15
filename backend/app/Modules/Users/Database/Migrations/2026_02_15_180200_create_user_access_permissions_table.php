<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("user_access_permissions", function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid("user_id")->constrained("users")->cascadeOnDelete();
            $table
                ->foreignUuid("permission_id")
                ->constrained("access_permissions")
                ->cascadeOnDelete();
            $table->boolean("allowed");
            $table->timestamps();

            $table->unique(["user_id", "permission_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("user_access_permissions");
    }
};
