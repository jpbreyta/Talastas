<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionAnalytics extends Model
{
    use HasFactory;

    protected $table = 'institution_analytics';

    protected $fillable = [
        'institution_id',

        'gap_score',
        'infrastructure_deficit_score',
        'connectivity_divide_score',
        'technological_obsolescence_score',
        'risk_level',

        'infrastructure_narrative',
        'policy_recommendation',
        'recommendations_detail',
        'ai_priorities',

        'analysis_date',

        'processing_token',
        'processing_status',
        'processing_stage',
        'processing_started_at',
        'processing_completed_at',
        'processing_error',
    ];

    protected $hidden = ['processing_error'];

    protected $casts = [
        'institution_id' => 'integer',

        'gap_score' => 'float',
        'infrastructure_deficit_score' => 'float',
        'connectivity_divide_score' => 'float',
        'technological_obsolescence_score' => 'float',

        'recommendations_detail' => 'array',
        'ai_priorities' => 'array',
        'analysis_date' => 'datetime',

        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];


    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
