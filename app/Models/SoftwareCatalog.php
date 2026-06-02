<?php

namespace App\Models;

use Database\Factories\SoftwareCatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareCatalog extends Model
{
    /** @use HasFactory<SoftwareCatalogFactory> */
    use HasFactory;

    protected $table = 'software_catalog';

    protected $fillable = [
        'name',
        'version',
        'publisher',
        'is_standard',
    ];

    protected function casts(): array
    {
        return [
            'is_standard' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BookingSoftware, $this>
     */
    public function bookingSoftware(): HasMany
    {
        return $this->hasMany(BookingSoftware::class);
    }
}
