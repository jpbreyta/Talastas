<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_records', function (Blueprint $table) {
            $table->id('audit_id');

            $table->foreignId('institution_id')
                ->constrained('institutions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('audit_date');
            $table->string('validation_status', 50);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['institution_id', 'audit_date']);
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_records');
    }
};
