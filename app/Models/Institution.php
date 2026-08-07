<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Institution extends Model
{
    use HasFactory;

    public const INVENTORY_SUMMARY_FIELDS = [
        'number_of_computers',
        'has_computer_lab',
        'has_projected_whiteboard',
        'has_smart_tv',
        'has_public_address_system',
    ];

    protected $table = 'institutions';

    protected $fillable = [
        'name',
        'type',
        'barangay_id',
        'latitude',
        'longitude',
        'location_geojson',
        'location_bounding_box',
        'nominatim_place_id',
        'osm_type',
        'osm_id',
        'student_population',
        'staff_population',
        'isp_provider',
        'internet_speed',
        'api_key',
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
        'latitude' => 'float',
        'longitude' => 'float',
        'location_geojson' => 'array',
        'location_bounding_box' => 'array',
        'nominatim_place_id' => 'integer',
        'osm_id' => 'integer',
        'internet_speed' => 'float',

        'student_population' => 'integer',
        'staff_population' => 'integer',
        'number_of_computers' => 'integer',
        'data_completeness_score' => 'float',

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

    protected static function booted(): void
    {
        static::creating(function (Institution $institution): void {
            $institution->forceFill(
                $institution->inventorySummaryDefaults()
            );
        });

        static::updating(function (Institution $institution): void {
            foreach (self::INVENTORY_SUMMARY_FIELDS as $field) {
                if ($institution->isDirty($field)) {
                    $institution->setAttribute(
                        $field,
                        $institution->getOriginal($field)
                    );
                }
            }
        });
    }

    public function institutionAnalytics(): HasOne
    {
        return $this->hasOne(
            InstitutionAnalytics::class,
            'institution_id',
            'id'
        );
    }

    public function ictInventory(): HasMany
    {
        return $this->hasMany(
            IctInventory::class,
            'institution_id',
            'id'
        );
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'audited_by',
            'user_id'
        );
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(
            Barangay::class,
            'barangay_id',
            'id'
        );
    }

    public function getAnalysisPopulation(): int
    {
        if ($this->type === 'School') {
            return $this->student_population ?? 0;
        }

        return $this->staff_population ?? 0;
    }

    public function applyInventorySummary(array $summary): void
    {
        $allowedSummary = array_intersect_key(
            $summary,
            array_flip(self::INVENTORY_SUMMARY_FIELDS)
        );

        $this->forceFill($allowedSummary)->saveQuietly();
    }

    protected function inventorySummaryDefaults(): array
    {
        return [
            'number_of_computers' => 0,
            'has_computer_lab' => false,
            'has_projected_whiteboard' => false,
            'has_smart_tv' => false,
            'has_public_address_system' => false,
        ];
    }
}
