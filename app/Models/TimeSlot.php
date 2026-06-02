<?php

namespace App\Models;

use Database\Factories\TimeSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'slot_date',
        'start_time',
        'end_time',
        'calendar_week',
        'status',
        'capacity',
        'booked_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'calendar_week' => 'integer',
            'capacity' => 'integer',
            'booked_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->booked_count < $this->capacity;
    }
}
