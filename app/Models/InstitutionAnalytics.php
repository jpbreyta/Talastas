<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',

        'gap_score',
        'risk_level',
        'data_completeness_score',

        'infrastructure_deficit_score',
        'connectivity_divide_score',
        'technological_obsolescence_score',

        'infrastructure_narrative',
        'policy_recommendation',
        'recommendations_detail',

        'asset_to_user_ratio',
        'analysis_date',
    ];

    protected $casts = [
        'institution_id' => 'integer',

        'gap_score' => 'float',
        'data_completeness_score' => 'float',

        'infrastructure_deficit_score' => 'float',
        'connectivity_divide_score' => 'float',
        'technological_obsolescence_score' => 'float',

        'asset_to_user_ratio' => 'float',

        'recommendations_detail' => 'array',
        'analysis_date' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(
            Institution::class,
            'institution_id',
            'id'
        );
    }
}