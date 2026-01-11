<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // admin, manager, staff
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_roles');
    }
};
