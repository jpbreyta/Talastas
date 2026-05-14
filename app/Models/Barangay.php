<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'district',
        'geometry', // GeoJSON MultiPolygon geometry
        'population',
        'total_institutions',
        'notes',
    ];

    protected $casts = [
        'geometry' => 'array',
        'population' => 'integer',
    ];

    public function institutions()
    {
        return $this->hasMany(Institution::class, 'barangay_id');
    }
}
