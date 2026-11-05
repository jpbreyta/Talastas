<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestampTz('email_mfa_confirmed_at')->nullable();
        });

        Schema::create('email_verification_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context_hash', 64);
            $table->unsignedInteger('security_version');
            $table->string('code_hash', 64)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestampTz('requested_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'context_hash']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            // The Laravel server owns this table; public Supabase API roles get no access.
            DB::statement('ALTER TABLE email_verification_codes ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('email_mfa_confirmed_at'));
    }
};
