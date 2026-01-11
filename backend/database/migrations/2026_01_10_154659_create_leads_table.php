<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('activity_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('child_id')->nullable();

            $table->string('child_name')->nullable();
            $table->date('child_birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->enum('status', ['new', 'contacted', 'registered', 'cancelled'])->default('new');

            $table->timestamps();

            $table->foreign('activity_id')
                ->references('id')
                ->on('activities')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('child_id')
                ->references('id')
                ->on('children')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
