<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 60)->default('general')->index();
            $table->string('key', 100);
            $table->string('locale', 10)->default('fr');
            $table->jsonb('value')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestampsTz();
            $table->unique(['group', 'key', 'locale']);
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('location', 40)->default('primary')->index();
            $table->string('locale', 10)->default('fr');
            $table->string('label', 80);
            $table->string('url', 2048);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('opens_new_tab')->default(false);
            $table->ulid('parent_id')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['location', 'locale', 'position']);
        });

        Schema::table('navigation_items', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('navigation_items')->nullOnDelete();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('disk', 40)->default('local');
            $table->string('path', 1024)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('sha256', 64)->unique();
            $table->string('alt_text', 180)->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('credit', 180)->nullable();
            $table->string('source_path', 1024)->nullable()->index();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('usable_type', 160);
            $table->string('usable_id', 40);
            $table->string('field', 80);
            $table->timestampsTz();
            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'field'], 'media_usage_unique');
            $table->index(['usable_type', 'usable_id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 120)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->foreignUlid('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('legacy_image_path', 1024)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('creation_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 120)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->foreignUlid('cover_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('legacy_folder', 1024)->nullable();
            $table->string('legacy_cover', 255)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('creation_category_media', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('creation_category_id')->constrained('creation_categories')->cascadeOnDelete();
            $table->foreignUlid('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('alt_text', 180)->nullable();
            $table->timestampsTz();
            $table->unique(['creation_category_id', 'media_asset_id']);
            $table->index(['creation_category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creation_category_media');
        Schema::dropIfExists('creation_categories');
        Schema::dropIfExists('services');
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('site_settings');
    }
};
