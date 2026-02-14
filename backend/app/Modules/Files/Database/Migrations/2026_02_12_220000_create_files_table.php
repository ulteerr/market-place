<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("files", function (Blueprint $table): void {
            $table->uuid("id")->primary();
            $table->string("disk")->default("public");
            $table->string("path");
            $table->string("original_name");
            $table->string("mime_type")->nullable();
            $table->unsignedBigInteger("size")->default(0);
            $table->string("collection")->default("default")->index();
            $table->nullableUuidMorphs("fileable");
            $table->timestamps();

            $table->index(["fileable_type", "fileable_id", "collection"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("files");
    }
};
