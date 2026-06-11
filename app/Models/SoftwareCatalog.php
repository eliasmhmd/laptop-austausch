<?php

namespace App\Models;

use Database\Factories\SoftwareCatalogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareCatalog extends Model
{
    /** @use HasFactory<SoftwareCatalogFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    protected $table = 'software_catalog';

    protected $fillable = [
        'name',
        'version',
        'publisher',
        'is_standard',
        'status',
        'submitted_by',
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

    /**
     * Mitarbeiter:in, die/der den Eintrag zur Prüfung eingereicht hat (nur bei „pending").
     *
     * @return BelongsTo<Employee, $this>
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'submitted_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Freigegebene Software – für alle Mitarbeitenden sichtbar.
     *
     * @param  Builder<SoftwareCatalog>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Eingereicht, aber noch nicht freigegeben.
     *
     * @param  Builder<SoftwareCatalog>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }
}
