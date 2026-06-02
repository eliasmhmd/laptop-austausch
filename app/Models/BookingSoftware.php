<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSoftware extends Model
{
    protected $table = 'booking_software';

    protected $fillable = [
        'booking_id',
        'software_catalog_id',
        'custom_software_name',
        'is_custom',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<SoftwareCatalog, $this>
     */
    public function softwareCatalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class);
    }
}
