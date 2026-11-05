<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Institution extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        if (!$this->exists || $this->isDirty(['name', 'barangay_id'])) {
            $duplicate = static::query()
                ->where('barangay_id', $this->barangay_id)
                ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$this->name])
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
                ->exists();

            if ($duplicate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => 'This institution already exists in the selected barangay.',
                ]);
            }
        }

        try {
            return parent::save($options);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            // The database also rejects duplicates when concurrent requests
            // both pass the check above before either insert commits.
            if (!str_contains($exception->getMessage(), 'institutions_barangay_name_unique')) {
                throw $exception;
            }

            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'This institution already exists in the selected barangay.',
            ]);
        }
    }

    protected $table = 'institutions';

    protected $fillable = [
        'q10_number_of_connections',
        'name',
        'type',
        'barangay_id',
        'location_source',
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
        'isp_connection_plan',
        'has_internet_connection',
        'has_backup_internet_link',
        'has_public_access_network',
        'bandwidth_allocation_mbps',
        'download_speed_mbps',
        'upload_speed_mbps',
        'latency_ms',
        'throughput_mbps',
        'internet_speed',
        'api_key',
        'has_computer_lab',
        'notes',
        'data_completeness_score',
        'requires_audit',
        'last_audited_at',
        'audited_by',
        'audit_notes',
        'is_active',
    ];

    protected $hidden = [
        'location_geometry',
        'api_key',
    ];

    protected $casts = [
        'q10_number_of_connections' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'location_geojson' => 'array',
        'location_bounding_box' => 'array',
        'nominatim_place_id' => 'integer',
        'osm_id' => 'integer',
        'student_population' => 'integer',
        'staff_population' => 'integer',
        'has_internet_connection' => 'boolean',
        'has_backup_internet_link' => 'boolean',
        'has_public_access_network' => 'boolean',
        'bandwidth_allocation_mbps' => 'float',
        'download_speed_mbps' => 'float',
        'upload_speed_mbps' => 'float',
        'latency_ms' => 'float',
        'throughput_mbps' => 'float',
        'internet_speed' => 'float',
        'number_of_computers' => 'integer',
        'data_completeness_score' => 'float',
        'has_computer_lab' => 'boolean',
        'has_projected_whiteboard' => 'boolean',
        'has_smart_tv' => 'boolean',
        'has_public_address_system' => 'boolean',
        'needs_maintenance' => 'boolean',
        'requires_audit' => 'boolean',
        'is_active' => 'boolean',
        'last_audited_at' => 'datetime',
    ];

    public function institutionAnalytics(): HasOne
    {
        return $this->hasOne(InstitutionAnalytics::class, 'institution_id');
    }

    public function internetConnections(): HasMany
    {
        return $this->hasMany(InstitutionInternetConnection::class)->orderBy('connection_number');
    }

    public function ictInventory(): HasMany
    {
        return $this->hasMany(IctInventory::class, 'institution_id');
    }

    public function auditRecords(): HasMany
    {
        return $this->hasMany(AuditRecord::class, 'institution_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

}
