<?php

use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('template', 40)->default(PageTemplate::Editorial->value);
            $table->string('status', 24)->default(ContentStatus::Draft->value)->index();
            $table->boolean('is_home')->default(false)->index();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestampTz('scheduled_at')->nullable()->index();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('slug', 120);
            $table->string('title', 120);
            $table->string('h1', 100);
            $table->text('intro')->nullable();
            $table->string('seo_title', 80)->nullable();
            $table->string('seo_description', 200)->nullable();
            $table->string('og_title', 100)->nullable();
            $table->string('og_description', 240)->nullable();
            $table->ulid('og_image_id')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('links_followed')->default(true);
            $table->timestampsTz();
            $table->unique(['page_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedSmallInteger('position')->default(0);
            $table->jsonb('settings')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestampTz('visible_from')->nullable();
            $table->timestampTz('visible_until')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['page_id', 'position']);
        });

        Schema::create('page_block_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('block_id')->constrained('page_blocks')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->jsonb('content');
            $table->timestampsTz();
            $table->unique(['block_id', 'locale']);
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('pages')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 24);
            $table->jsonb('snapshot');
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['page_id', 'version']);
        });

        Schema::create('publication_releases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->bigInteger('sequence')->unique();
            $table->string('status', 24)->index();
            $table->string('manifest_path', 500)->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('published_at')->nullable()->index();
            $table->ulid('rollback_of_id')->nullable()->index();
            $table->timestampsTz();
        });

        Schema::table('publication_releases', function (Blueprint $table) {
            $table->foreign('rollback_of_id')
                ->references('id')
                ->on('publication_releases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_releases');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('page_block_translations');
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
