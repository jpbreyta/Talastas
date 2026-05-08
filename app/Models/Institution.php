<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'sector_type',
        'barangay_name',
        'latitude',
        'longitude',
        'student_population',
        'staff_population',
        'isp_provider',
        'internet_speed',
        'has_computer_lab',
        'number_of_computers',
        'has_projected_whiteboard',
        'has_smart_tv',
        'has_public_address_system',
        'needs_maintenance',
        'is_pilot_site',
        'data_completeness_score',
        'requires_audit',
        'last_audited_at',
        'audited_by',
        'audit_notes',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'internet_speed' => 'decimal:2',
        'has_computer_lab' => 'boolean',
        'has_projected_whiteboard' => 'boolean',
        'has_smart_tv' => 'boolean',
        'has_public_address_system' => 'boolean',
        'needs_maintenance' => 'boolean',
        'is_pilot_site' => 'boolean',
        'requires_audit' => 'boolean',
        'is_active' => 'boolean',
        'last_audited_at' => 'datetime',
        'data_completeness_score' => 'integer',
    ];

    public function institutionAnalytics()
    {
        return $this->hasOne(InstitutionAnalytics::class, 'institution_id');
    }

    public function ictInventory()
    {
        return $this->hasMany(IctInventory::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }
}
