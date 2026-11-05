<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->selectRaw('LOWER(TRIM(email)) AS normalized')->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Resolve normalized email collisions before installing account security. No accounts were changed.');
        }
        DB::statement('CREATE UNIQUE INDEX users_email_normalized_unique ON users (LOWER(TRIM(email)))');
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->timestamp('invitation_accepted_at')->nullable();
            $t->timestamp('deactivated_at')->nullable();
            $t->unsignedBigInteger('deactivated_by')->nullable();
            $t->unsignedInteger('security_version')->default(1);
            $t->text('mfa_secret')->nullable();
            $t->json('mfa_recovery_codes')->nullable();
            $t->timestamp('mfa_confirmed_at')->nullable();
            $t->bigInteger('mfa_last_step')->default(-1);
        });
        Schema::table('terms_acceptances', function (Blueprint $t) {
            $t->string('privacy_version', 80)->nullable();
            $t->string('privacy_content_hash', 64)->nullable();
            $t->longText('privacy_snapshot')->nullable();
        });
        Schema::create('user_invitations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('email')->index();
            $t->string('role', 20);
            $t->string('token_hash', 64)->nullable();
            $t->unsignedInteger('generation')->default(1);
            $t->string('status', 20)->default('pending');
            $t->string('delivery_status', 24)->default('queued');
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedBigInteger('revoked_by')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
        DB::statement("CREATE UNIQUE INDEX invitations_pending_email_unique ON user_invitations (email) WHERE status = 'pending'");
        Schema::create('invitation_institution', function (Blueprint $t) {
            $t->uuid('invitation_id');
            $t->foreign('invitation_id')->references('id')->on('user_invitations')->cascadeOnDelete();
            $t->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $t->primary(['invitation_id', 'institution_id']);
        });
        Schema::create('account_recoveries', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->string('purpose', 24)->default('password_reset');
            $t->unsignedInteger('generation')->default(1);
            $t->string('code_hash')->nullable();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('requested_at');
            $t->timestamp('verified_at')->nullable();
            $t->timestamp('consumed_at')->nullable();
            $t->string('grant_hash', 64)->nullable();
            $t->string('delivery_status', 24)->default('queued');
            $t->timestamps();
        });
        Schema::create('security_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('actor_id')->nullable()->index();
            $t->unsignedBigInteger('target_id')->nullable()->index();
            $t->uuid('invitation_id')->nullable()->index();
            $t->string('event', 80);
            $t->string('outcome', 24);
            $t->string('request_fingerprint', 64)->nullable();
            $t->timestamp('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('terms_acceptances', fn (Blueprint $t) => $t->dropColumn(['privacy_version', 'privacy_content_hash', 'privacy_snapshot']));
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('account_recoveries');
        Schema::dropIfExists('invitation_institution');
        Schema::dropIfExists('user_invitations');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_active', 'invitation_accepted_at', 'deactivated_at', 'deactivated_by', 'security_version', 'mfa_secret', 'mfa_recovery_codes', 'mfa_confirmed_at', 'mfa_last_step']));
        DB::statement('DROP INDEX IF EXISTS users_email_normalized_unique');
    }
};
