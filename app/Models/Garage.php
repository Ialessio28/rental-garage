<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A garage that can be rented. Types: bicycle, car, electric.
 */
class Garage extends Model
{
    /** Code prefix for each garage type, e.g. S001, C010, E005. */
    public const PREFIXES = [
        'bicycle' => 'S',
        'car' => 'C',
        'electric' => 'E',
    ];

    /** Highest number a garage code can have (3 digits). */
    public const MAX_PER_TYPE = 999;

    protected $fillable = ['code', 'type', 'daily_price', 'kwh_price'];

    /**
     * All rentals of this garage.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Only garages that are free on every day from start to end
     * (no rental of the garage overlaps that period).
     *
     * @param  Builder  $query  the garage query
     * @param  Carbon   $start  first day of the period
     * @param  Carbon   $end    last day of the period
     * @return Builder  the filtered query
     */
    public function scopeAvailableBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereDoesntHave('rentals', fn (Builder $rental) => $rental->overlapping($start, $end));
    }

    /**
     * Create several garages of one type at once. Codes continue from the
     * last garage of that type (S001, S002, ...).
     *
     * @param  string    $type        bicycle | car | electric
     * @param  int       $count       how many garages to create
     * @param  int       $dailyPrice  daily rent price
     * @param  int|null  $kwhPrice    charging price per kWh (electric only)
     * @return void
     */
    public static function createBatch(string $type, int $count, int $dailyPrice, ?int $kwhPrice): void
    {
        DB::transaction(function () use ($type, $count, $dailyPrice, $kwhPrice) {
            $lastNumber = self::where('type', $type)->count();

            for ($i = 1; $i <= $count; $i++) {
                self::create([
                    'code' => self::PREFIXES[$type] . str_pad((string) ($lastNumber + $i), 3, '0', STR_PAD_LEFT),
                    'type' => $type,
                    'daily_price' => $dailyPrice,
                    'kwh_price' => $type === 'electric' ? $kwhPrice : null,
                ]);
            }
        });
    }
}
