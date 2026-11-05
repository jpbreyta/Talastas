<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Historical accounts stay unenrolled in browser trust until a real factor is verified.
            $table->timestampTz('mfa_verified_at')->nullable();
        });
        Schema::create('mfa_trusted_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('security_version');
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('previous_token_hash', 64)->nullable();
            $table->string('session_hash', 64);
            $table->string('browser_hash', 64);
            $table->string('network_hash', 64);
            $table->timestampTz('last_verified_at');
            $table->timestampTz('last_activity_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('trusted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_trusted_devices');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('mfa_verified_at'));
    }
};
