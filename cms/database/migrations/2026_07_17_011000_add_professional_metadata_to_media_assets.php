<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table): void {
            $table->string('title', 180)->nullable()->after('sha256');
            $table->boolean('is_decorative')->default(false)->after('alt_text')->index();
            $table->string('rights', 180)->nullable()->after('credit');
            $table->date('taken_at')->nullable()->after('rights');
            $table->jsonb('tags')->nullable()->after('taken_at');
            $table->decimal('focal_x', 5, 4)->default(0.5)->after('tags');
            $table->decimal('focal_y', 5, 4)->default(0.5)->after('focal_x');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table): void {
            $table->dropColumn(['title', 'is_decorative', 'rights', 'taken_at', 'tags', 'focal_x', 'focal_y']);
        });
    }
};
