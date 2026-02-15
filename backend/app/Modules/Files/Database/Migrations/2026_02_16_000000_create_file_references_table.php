<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("file_references", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->uuid("file_id");
            $table->string("owner_type", 100);
            $table->string("owner_id");
            $table->json("meta")->nullable();
            $table->timestamps();

            $table->foreign("file_id")->references("id")->on("files")->cascadeOnDelete();
            $table->index("file_id");
            $table->index(["owner_type", "owner_id"]);
            $table->unique(["file_id", "owner_type", "owner_id"], "file_references_owner_unique");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("file_references");
    }
};
