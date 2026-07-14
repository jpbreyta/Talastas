<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'type',
        'barangay_id',

        'latitude',
        'longitude',

        'student_population',
        'staff_population',

        'isp_provider',
        'internet_speed',
        'api_key',

        'has_computer_lab',
        'number_of_computers',
        'has_projected_whiteboard',
        'has_smart_tv',
        'has_public_address_system',

        'needs_maintenance',
        'notes',

        'is_pilot_site',
        'data_completeness_score',
        'requires_audit',

        'last_audited_at',
        'audited_by',
        'audit_notes',

        'is_active',
    ];
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'internet_speed' => 'decimal:2',

        'student_population' => 'integer',
        'staff_population' => 'integer',
        'number_of_computers' => 'integer',
        'data_completeness_score' => 'integer',

        'has_computer_lab' => 'boolean',
        'has_projected_whiteboard' => 'boolean',
        'has_smart_tv' => 'boolean',
        'has_public_address_system' => 'boolean',
        'needs_maintenance' => 'boolean',

        'is_pilot_site' => 'boolean',
        'requires_audit' => 'boolean',
        'is_active' => 'boolean',

        'last_audited_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function institutionAnalytics()
    {
        return $this->hasOne(InstitutionAnalytics::class, 'institution_id');
    }

    public function ictInventory()
    {
        return $this->hasMany(IctInventory::class, 'institution_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    /**
     * Update or create analytics for this institution
     * Includes full heuristic analysis with recommendations
     */
    public function updateAnalytics()
    {
        $gapScore = \App\Helpers\GapScoreCalculator::computeScore($this);
        $dataCompleteness = \App\Helpers\GapScoreCalculator::computeDataCompleteness($this);

        // Get full heuristic analysis with recommendations
        $service = app(\App\Services\GeminiService::class);
        $analysis = $service->analyzeInfrastructure($this);

        $this->institutionAnalytics()->updateOrCreate(
            ['institution_id' => $this->id],
            [
                'gap_score' => $gapScore,
                'data_completeness_score' => $dataCompleteness,
                'infrastructure_narrative' => $analysis['infrastructure_narrative'],
                'policy_recommendation' => $analysis['policy_recommendation'],
                'recommendations_detail' => $analysis['recommendations_detail'],
                'analysis_date' => now(),
            ]
        );

        // Also update the institution's data_completeness_score
        // Use withoutEvents to prevent infinite loop
        static::withoutEvents(function () use ($dataCompleteness) {
            $this->update(['data_completeness_score' => $dataCompleteness]);
        });
    }
}
