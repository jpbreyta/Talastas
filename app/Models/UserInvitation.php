<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['token_hash'];

    protected $casts = ['generation' => 'integer', 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime', 'sent_at' => 'datetime'];

    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'invitation_institution', 'invitation_id', 'institution_id');
    }
}
