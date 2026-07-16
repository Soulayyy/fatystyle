<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 1024)->unique();
            $table->string('mime_type', 120)->default('image/webp');
            $table->string('format', 20)->default('webp');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedTinyInteger('quality')->nullable();
            $table->timestampsTz();
            $table->unique(['media_asset_id', 'width', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_variants');
    }
};
