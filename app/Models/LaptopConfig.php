<?php

namespace App\Models;

use Database\Factories\LaptopConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaptopConfig extends Model
{
    /** @use HasFactory<LaptopConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'old_pc_nummer',
        'old_serial_number',
        'old_manufacturer',
        'old_model',
        'old_cpu',
        'old_ram_gb',
        'old_storage_gb',
        'old_storage_type',
        'old_operating_system',
        'old_inventory_number',
        'new_pc_nummer',
        'new_serial_number',
        'new_inventory_number',
    ];

    protected function casts(): array
    {
        return [
            'old_ram_gb' => 'integer',
            'old_storage_gb' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
