<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditRecord extends Model
{
    protected $fillable = [
        'institution_id',
        'user_id',
        'audit_date',
        'validation_status',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
