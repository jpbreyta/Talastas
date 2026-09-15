<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
