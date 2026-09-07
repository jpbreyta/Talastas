<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditRecord extends Model
{
    protected $table = 'audit_records';

    protected $primaryKey = 'audit_id';

    protected $fillable = [
        'institution_id',
        'user_id',
        'audit_date',
        'validation_status',
        'notes',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'user_id' => 'integer',
        'audit_date' => 'date',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(
            Institution::class,
            'institution_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
