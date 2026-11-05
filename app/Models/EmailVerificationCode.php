<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['code_hash', 'context_hash'];

    protected $casts = [
        'security_version' => 'integer', 'attempts' => 'integer',
        'requested_at' => 'datetime', 'expires_at' => 'datetime', 'consumed_at' => 'datetime',
    ];
}
