<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'time_slot_id',
        'status',
        'cancellation_reason',
        'unplanned_note',
        'booked_at',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<TimeSlot, $this>
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * @return HasOne<LaptopConfig, $this>
     */
    public function laptopConfig(): HasOne
    {
        return $this->hasOne(LaptopConfig::class);
    }

    /**
     * @return HasMany<BookingSoftware, $this>
     */
    public function software(): HasMany
    {
        return $this->hasMany(BookingSoftware::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'confirmed';
    }
}
