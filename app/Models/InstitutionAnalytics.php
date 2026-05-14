<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'gap_score',
        'data_completeness_score',
        'infrastructure_narrative',
        'policy_recommendation',
        'recommendations_detail',
        'analysis_date',
        'asset_to_user_ratio',
        'risk_level',
    ];

    protected $casts = [
        'gap_score' => 'decimal:2',
        'data_completeness_score' => 'integer',
        'recommendations_detail' => 'array',
        'analysis_date' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
