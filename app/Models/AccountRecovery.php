<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRecovery extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['code_hash', 'grant_hash'];

    protected $casts = ['generation' => 'integer', 'expires_at' => 'datetime', 'requested_at' => 'datetime', 'verified_at' => 'datetime', 'consumed_at' => 'datetime'];
}
