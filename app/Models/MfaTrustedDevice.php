<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaTrustedDevice extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['token_hash', 'previous_token_hash', 'session_hash', 'browser_hash', 'network_hash'];

    protected $casts = [
        'security_version' => 'integer',
        'last_verified_at' => 'datetime', 'last_activity_at' => 'datetime',
        'expires_at' => 'datetime', 'trusted_at' => 'datetime', 'revoked_at' => 'datetime',
    ];
}
