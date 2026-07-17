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

    /*
    |--------------------------------------------------------------------------
    | Data Quality Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Compute data completeness score (0-100) for quality flagging
     */
    public function computeDataCompleteness(): int
    {
        $requiredFields = [
            'name', 'type', 'barangay_id', 'latitude', 'longitude',
            'has_computer_lab', 'has_smart_tv', 'has_public_address_system',
        ];

        $optionalFields = [
            'student_population', 'staff_population', 'internet_speed',
            'number_of_computers', 'has_projected_whiteboard', 'needs_maintenance',
        ];

        $requiredScore = 0;
        foreach ($requiredFields as $field) {
            if (! empty($this->$field) || $this->$field === false) {
                $requiredScore += 20; 
            }
        }

        $optionalScore = 0;
        $optionalCount = count($optionalFields);
        foreach ($optionalFields as $field) {
            if (! empty($this->$field) || $this->$field === false || $this->$field === 0) {
                $optionalScore += (60 / $optionalCount); 
            }
        }

        return min(100, (int) round($requiredScore + $optionalScore));
    }
}
