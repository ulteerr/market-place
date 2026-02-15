<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("access_permissions", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("code")->unique();
            $table->string("scope");
            $table->string("label")->nullable();
            $table->timestamps();

            $table->index("scope");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("access_permissions");
    }
};
