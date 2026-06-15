<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Buchungsstatus, die als „hat einen Termin“ gelten – bestätigt (steht noch
     * an) oder abgeschlossen (Austausch erledigt). Storniert/krank/nicht
     * erschienen zählen NICHT, diese Personen müssen (neu) buchen.
     *
     * @var list<string>
     */
    public const SETTLED_BOOKING_STATUSES = ['confirmed', 'completed'];

    /**
     * Mitarbeitende ohne gültigen Termin – brauchen also noch eine Buchung.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeOhneTermin(Builder $query): Builder
    {
        return $query->whereDoesntHave(
            'bookings',
            fn (Builder $q) => $q->whereIn('status', self::SETTLED_BOOKING_STATUSES),
        );
    }
}
