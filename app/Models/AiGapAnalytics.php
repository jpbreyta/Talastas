<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGapAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'gap_score',
        'infrastructure_narrative',
        'policy_recommendation',
        'recommendations_detail',
        'analysis_date',
    ];

    protected $casts = [
        'gap_score' => 'decimal:2',
        'recommendations_detail' => 'array',
        'analysis_date' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
