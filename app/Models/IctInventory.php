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
    ];

    protected $table = 'ict_inventory';

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'institution_id',
        'category',
        'asset_name',
        'brand',
        'model',
        'quantity',
        'condition',
        'acquisition_year',
        'status',
        'location',
        'specifications',
        'needs_maintenance',
        'notes',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'quantity' => 'integer',
        'acquisition_year' => 'integer',
        'specifications' => 'array',
        'needs_maintenance' => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Convenience scope for inventory rows that are currently active.
     *
     * LOWER() is intentionally used while the existing controllers/views are
     * being refactored so legacy values such as "Active" still behave correctly.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereRaw('LOWER(status) = ?', [self::STATUS_ACTIVE]);
    }
}
