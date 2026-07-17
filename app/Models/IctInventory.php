<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IctInventory extends Model
{
    protected $table = 'ict_inventory';

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'institution_id',
        'category',
        'condition',
        'acquisition_year',
        'status',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'acquisition_year' => 'integer',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(
            Institution::class,
            'institution_id',
            'id'
        );
    }
}