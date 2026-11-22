<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rental of one garage by one tenant, for one vehicle.
 */
class Rental extends Model
{
    /** Maximum size of a payment proof image, in kilobytes (2 MB). */
    public const MAX_PROOF_KB = 2048;

    protected $fillable = [
        'user_id', 'garage_id', 'vehicle_type', 'plate_number', 'brand_model',
        'battery_capacity', 'current_charge', 'rental_type', 'days',
        'start_date', 'end_date', 'rent_cost', 'charge_cost', 'total_cost', 'payment_proof',
    ];

    /**
     * Attribute casts.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'rent_cost' => 'integer',
            'charge_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    /**
     * The tenant who made this rental.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The rented garage.
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Extensions the admin made to this rental.
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class);
    }

    /**
     * Only rentals that are running on the given date (start <= date <= end).
     *
     * @param  Builder  $query  the rental query
     * @param  Carbon   $date   the date to check
     * @return Builder  the filtered query
     */
    public function scopeActiveOn(Builder $query, Carbon $date): Builder
    {
        return $query->overlapping($date, $date);
    }

    /**
     * Only rentals that share at least one day with the period start..end
     * (rental start <= period end and rental end >= period start).
     *
     * @param  Builder  $query  the rental query
     * @param  Carbon   $start  first day of the period
     * @param  Carbon   $end    last day of the period
     * @return Builder  the filtered query
     */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());
    }
}
