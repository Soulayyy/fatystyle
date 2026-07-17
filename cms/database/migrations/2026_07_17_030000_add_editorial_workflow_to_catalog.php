<?php

use App\Enums\ContentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('status', ContentStatus::Draft->value)->update([
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
        ]);

        foreach (['services', 'creation_categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('status', 24)->default(ContentStatus::Published->value)->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->timestampTz('scheduled_at')->nullable()->index();
                $table->timestampTz('published_at')->nullable()->index();
                $table->timestampTz('expires_at')->nullable()->index();
                $table->string('seo_title', 80)->nullable();
                $table->string('seo_description', 200)->nullable();
            });

            DB::table($tableName)->where('is_visible', false)->update(['status' => ContentStatus::Hidden->value]);
            DB::table($tableName)->where('is_visible', true)->update([
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
            ]);
        }

        Schema::table('services', function (Blueprint $table): void {
            $table->string('audience', 160)->nullable();
            $table->string('price_label', 120)->nullable();
            $table->string('duration_label', 120)->nullable();
            $table->string('cta_label', 80)->nullable();
            $table->string('cta_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['audience', 'price_label', 'duration_label', 'cta_label', 'cta_url']);
        });

        foreach (['services', 'creation_categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn([
                    'status', 'is_featured', 'scheduled_at', 'published_at', 'expires_at',
                    'seo_title', 'seo_description',
                ]);
            });
        }
    }
};
