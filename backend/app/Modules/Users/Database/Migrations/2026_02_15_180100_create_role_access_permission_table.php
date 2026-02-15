<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("role_access_permission", function (Blueprint $table): void {
            $table->foreignUuid("role_id")->constrained("roles")->cascadeOnDelete();

            $table
                ->foreignUuid("permission_id")
                ->constrained("access_permissions")
                ->cascadeOnDelete();

            $table->primary(["role_id", "permission_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("role_access_permission");
    }
};
