<?php

namespace App\Models;

use Database\Factories\AdminUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory, Notifiable;

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

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Zeitfenster, die dieser Admin erzeugt hat.
     *
     * @return HasMany<TimeSlot, $this>
     */
    public function createdTimeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'created_by');
    }

    /**
     * Beide Rollen (admin + viewer) dürfen das Panel öffnen.
     * Schreibrechte werden je Resource über die Rolle gesteuert.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'viewer'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
