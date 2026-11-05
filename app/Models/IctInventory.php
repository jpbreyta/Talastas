<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IctInventory extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_RETIRED = 'retired';
    public const STATUS_DISPOSED = 'disposed';

    public const CATEGORY_COMPUTER = 'computer';
    public const CATEGORY_DESKTOP_COMPUTER = 'desktop_computer';
    public const CATEGORY_LAPTOP = 'laptop';
    public const CATEGORY_TABLET = 'tablet';
    public const CATEGORY_SMART_PHONE = 'smart_phone';
    public const CATEGORY_WORKSTATION = 'workstation';
    public const CATEGORY_PDA = 'pda';
    public const CATEGORY_SMART_TV = 'smart_tv';
    public const CATEGORY_PROJECTOR = 'projector';
    public const CATEGORY_PROJECTED_WHITEBOARD = 'projected_whiteboard';
    public const CATEGORY_PA_SYSTEM = 'pa_system';

    /**
     * Categories counted toward the institution's computer total.
     */
    public const COMPUTER_CATEGORIES = [
        self::CATEGORY_COMPUTER,
        self::CATEGORY_DESKTOP_COMPUTER,
        self::CATEGORY_LAPTOP,
        self::CATEGORY_TABLET,
        self::CATEGORY_SMART_PHONE,
        self::CATEGORY_WORKSTATION,
        self::CATEGORY_PDA,
    ];

    protected $table = 'ict_inventory';

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'institution_id',
        'component_id',
        'category',
        'component_type',
        'is_computing_device',
        'asset_name',
        'brand',
        'model',
        'total_count',
        'active_count',
        'retired_count',
        'functional_count',
        'defective_count',
        'defective_active_count',
        'functional_count_recorded',
        'quantity',
        'condition',
        'acquisition_year',
        'status',
        'specifications',
        'needs_maintenance',
        'notes',
    ];

    protected $appends = ['availability_status'];

    protected $casts = [
        'institution_id' => 'integer',
        'component_id' => 'integer',
        'is_computing_device' => 'boolean',
        'total_count' => 'integer',
        'active_count' => 'integer',
        'retired_count' => 'integer',
        'functional_count' => 'integer',
        'functional_count_recorded' => 'boolean',
        'defective_count' => 'integer',
        'defective_active_count' => 'integer',
        'quantity' => 'integer',
        'acquisition_year' => 'integer',
        'specifications' => 'array',
        'needs_maintenance' => 'boolean',
    ];

    /** Only explicitly encoded survey counts are presented as Functional. */
    public function getFunctionalCountAttribute($value): ?int
    {
        return ($this->attributes['functional_count_recorded'] ?? false)
            && $value !== null ? (int) $value : null;
    }

    public function getAvailabilityStatusAttribute(): string
    {
        return \App\Services\InventoryCounts::status(
            collect(['total_count', ...\App\Services\InventoryCounts::AVAILABILITY_FIELDS])
                ->mapWithKeys(fn ($field) => [$field => $this->{$field}])->all()
        );
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(IctComponent::class, 'component_id');
    }

    /**
     * Convenience scope for inventory rows that are currently active.
     *
     * LOWER() is intentionally used while the existing controllers/views are
     * being refactored so legacy values such as "Active" still behave correctly.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_count', '>', 0);
    }
}
