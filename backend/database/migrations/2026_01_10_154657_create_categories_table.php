<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('categories');
    }
};
