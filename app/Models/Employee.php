<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'kvgg_nummer',
        'vorname',
        'nachname',
        'email',
        'abteilung',
        'pc_nummer',
        'last_laptop_exchange',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_laptop_exchange' => 'date',
        ];
    }

    /**
     * Beim Login dient die pc_nummer als "Passwort". Sie liegt im Klartext vor,
     * daher prüfen wir sie direkt (kein Hash-Vergleich).
     */
    public function getAuthPassword(): string
    {
        return $this->pc_nummer;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->vorname} {$this->nachname}";
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Aktuell gültige Buchung (bestätigt), falls vorhanden.
     *
     * @return HasMany<Booking, $this>
     */
    public function activeBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', 'confirmed');
    }
}
