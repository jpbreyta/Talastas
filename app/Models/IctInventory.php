<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IctInventory extends Model
{
    protected $table = 'ict_inventory';

    protected $fillable = [
        'institution_id',
        'category',
        'condition',
        'acquisition_year',
        'status',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
