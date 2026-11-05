<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_acceptances', function (Blueprint $table): void {
            $table->id();
            // No name/email/IP/GPS duplication. Lifecycle must be approved before publication.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_version', 80);
            $table->string('requirement_version', 80);
            $table->string('content_hash', 64);
            $table->longText('policy_snapshot');
            $table->timestampTz('accepted_at');
            $table->unique(['user_id', 'policy_version']);
            $table->index(['user_id', 'requirement_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
    }
};
