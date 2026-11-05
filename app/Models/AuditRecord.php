<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditRecord extends Model
{
    use HasFactory;

    protected $table = 'audit_records';

    protected $primaryKey = 'audit_id';

    public $incrementing = true;

    protected $fillable = [
        'institution_id',
        'user_id',
        'audit_date',
        'validation_status',
        'notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_FLAGGED = 'flagged';
    public const STATUS_REVISION_REQUIRED = 'revision_required';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VALIDATED,
        self::STATUS_APPROVED,
        self::STATUS_FLAGGED,
        self::STATUS_REVISION_REQUIRED,
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isValidatedStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}