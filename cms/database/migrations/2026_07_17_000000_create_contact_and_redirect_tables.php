<?php

use App\Enums\ContactStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference', 24)->unique();
            $table->string('status', 24)->default(ContactStatus::New->value)->index();
            $table->string('name', 120);
            $table->string('email', 254)->index();
            $table->string('phone', 40)->nullable();
            $table->string('request_type', 120)->nullable()->index();
            $table->date('desired_date')->nullable();
            $table->text('message');
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('consent_at')->nullable();
            $table->timestampTz('received_at')->useCurrent()->index();
            $table->timestampTz('replied_at')->nullable();
            $table->string('source', 80)->default('website');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('redirect_rules', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 2048)->unique();
            $table->string('target_url', 2048);
            $table->unsignedSmallInteger('http_status')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestampTz('last_hit_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirect_rules');
        Schema::dropIfExists('contact_requests');
    }
};
