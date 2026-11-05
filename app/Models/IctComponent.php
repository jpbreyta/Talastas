<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IctComponent extends Model
{
    use HasFactory;

    public const TYPE_HARDWARE = 'hardware';
    public const TYPE_SOFTWARE = 'software';
    public const TYPE_NETWORK = 'network';
    public const TYPE_CONNECTIVITY = 'connectivity';

    protected $fillable = [
        'key',
        'name',
        'component_type',
        'subsection',
        'is_standard',
        'counts_toward_infrastructure',
        'is_computing_device',
        'is_active',
    ];

    protected $casts = [
        'is_standard' => 'boolean',
        'counts_toward_infrastructure' => 'boolean',
        'is_computing_device' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function inventory(): HasMany
    {
        return $this->hasMany(IctInventory::class, 'component_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
