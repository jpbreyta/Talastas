<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->enum('sector_type', ['School', 'Barangay Hall', 'City Government Office', 'Public Library', 'Health Center', 'Other Public'])->default('School')->after('type');
            $table->boolean('is_pilot_site')->default(true)->after('sector_type')->comment('Marked for pilot testing phase');
            $table->integer('data_completeness_score')->default(0)->after('is_pilot_site')->comment('0-100 score based on filled fields');
            $table->boolean('requires_audit')->default(true)->after('data_completeness_score')->comment('Flag for field verification needed');
            $table->timestamp('last_audited_at')->nullable()->after('requires_audit');
            $table->unsignedBigInteger('audited_by')->nullable()->after('last_audited_at');
            $table->foreign('audited_by')->references('id')->on('users')->onDelete('set null');
            $table->text('audit_notes')->nullable()->after('audited_by');
            $table->boolean('is_active')->default(true)->after('audit_notes');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropForeign(['audited_by']);
            $table->dropColumn([
                'sector_type',
                'is_pilot_site',
                'data_completeness_score',
                'requires_audit',
                'last_audited_at',
                'audited_by',
                'audit_notes',
                'is_active',
            ]);
        });
    }
};
