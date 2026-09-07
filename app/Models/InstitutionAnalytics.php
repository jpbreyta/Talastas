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

        'data_completeness_score',
        'asset_to_user_ratio',
        'risk_level',

        'infrastructure_narrative',
        'policy_recommendation',
        'recommendations_detail',

        'analysis_date',
    ];

    protected $casts = [
        'institution_id' => 'integer',

        'gap_score' => 'float',
        'infrastructure_deficit_score' => 'float',
        'connectivity_divide_score' => 'float',
        'technological_obsolescence_score' => 'float',
        'data_completeness_score' => 'float',
        'asset_to_user_ratio' => 'float',

        'recommendations_detail' => 'array',
        'analysis_date' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
