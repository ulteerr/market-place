<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("children", function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("user_id");

            $table->string("first_name");
            $table->string("last_name");
            $table->string("middle_name")->nullable();
            $table->string("gender")->nullable();
            $table->date("birth_date")->nullable();

            $table->timestamps();

            $table->foreign("user_id")->references("id")->on("users")->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("children");
    }
};
