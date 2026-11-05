<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $attributes = ['is_active' => true, 'security_version' => 1, 'mfa_last_step' => -1];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret', 'mfa_recovery_codes', 'mfa_last_step', 'security_version', 'mfa_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'security_version' => 'integer', 'is_active' => 'boolean', 'invitation_accepted_at' => 'datetime',
        'mfa_secret' => 'encrypted', 'mfa_recovery_codes' => 'array', 'mfa_confirmed_at' => 'datetime',
        'mfa_verified_at' => 'datetime',
        'email_mfa_confirmed_at' => 'datetime',
    ];

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function canEdit(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    public function canView(): bool
    {
        return in_array($this->role, ['admin', 'editor', 'viewer'], true);
    }

    public function canManageInstitutions(): bool
    {
        return $this->isAdmin();
    }

    public function canManageInventory(): bool
    {
        return $this->canEdit();
    }

    public function canDeleteInventory(): bool
    {
        return $this->isAdmin();
    }

    public function canRunAnalytics(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessInstitution(Institution|int $institution): bool
    {
        if ($this->isAdmin() || $this->isViewer()) {
            return true;
        }

        $institutionId = $institution instanceof Institution
            ? $institution->getKey()
            : $institution;

        if (! $this->isEditor()) {
            return false;
        }

        return $this->institutions()
            ->whereKey($institutionId)
            ->exists();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'System Administrator',
            'editor' => 'Barangay Focal Person / IT Coordinator',
            'viewer' => 'LGU City Planner',
            default => 'User',
        };
    }
}
