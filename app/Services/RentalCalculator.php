<?php

namespace App\Services;

// Library: Carbon (nesbot/carbon, MIT license), installed with Laravel.
// Purpose: date arithmetic (days in a month, adding days, difference in days).
// Docs: https://carbon.nesbot.com/docs
use Carbon\Carbon;

/**
 * All business rules for rental periods and prices, in one place.
 * It has no database access, so every rule can be unit tested directly.
 *
 * @version 1.0
 */
class RentalCalculator
{
    /** Monthly price = 25 x daily price. */
    public const MONTHLY_MULTIPLIER = 25;

    /** Which garage types each vehicle type may use. */
    public const ALLOWED_GARAGES = [
        'bicycle' => ['bicycle'],
        'motorcycle' => ['bicycle'],
        'car' => ['car'],
        'electric_car' => ['car', 'electric'],
    ];

    /**
     * Garage types a vehicle type may be parked in.
     *
     * @param  string  $vehicleType  bicycle | motorcycle | car | electric_car
     * @return string[]  allowed garage types; empty if the vehicle type is unknown
     */
    public function allowedGarageTypes(string $vehicleType): array
    {
        return self::ALLOWED_GARAGES[$vehicleType] ?? [];
    }

    /**
     * Longest daily rental allowed: days in the start month minus 1
     * (Feb = 27, Feb in a leap year = 28, Nov = 29, Dec = 30).
     *
     * @param  Carbon  $start  start date of the rental
     * @return int  maximum number of days for a daily rental
     */
    public function maxDailyDays(Carbon $start): int
    {
        return $start->daysInMonth - 1;
    }

    /**
     * Number of days a rental lasts. A monthly rental lasts the number of
     * days in its start month; a daily rental lasts the days chosen.
     *
     * @param  Carbon    $start       start date of the rental
     * @param  string    $rentalType  daily | monthly
     * @param  int|null  $days        days chosen (daily only, ignored for monthly)
     * @return int  length of the rental in days
     */
    public function rentalDays(Carbon $start, string $rentalType, ?int $days): int
    {
        if ($rentalType === 'monthly') {
            return $start->daysInMonth;
        }

        return (int) $days;
    }

    /**
     * Last day of the rental. The start day counts as day 1,
     * so a 1-day rental starts and ends on the same date.
     *
     * @param  Carbon  $start  start date of the rental (not modified)
     * @param  int     $days   length of the rental in days (at least 1)
     * @return Carbon  end date of the rental
     */
    public function endDate(Carbon $start, int $days): Carbon
    {
        return $start->copy()->addDays($days - 1);
    }

    /**
     * Rent price: monthly = 25 x daily price, daily = days x daily price.
     *
     * @param  int     $dailyPrice  garage price per day, in rupiah
     * @param  string  $rentalType  daily | monthly
     * @param  int     $days        length of the rental in days
     * @return int  rent price in rupiah
     */
    public function rentCost(int $dailyPrice, string $rentalType, int $days): int
    {
        if ($rentalType === 'monthly') {
            return $dailyPrice * self::MONTHLY_MULTIPLIER;
        }

        return $dailyPrice * $days;
    }

    /**
     * Charging price. Only electric garages charge, and always to full,
     * so the tenant pays for (capacity - current charge) kWh.
     * Example: 100 kWh battery holding 60 kWh -> pays for 40 kWh.
     *
     * @param  string  $garageType     bicycle | car | electric
     * @param  float   $capacity       battery capacity in kWh
     * @param  float   $currentCharge  energy in the battery now, in kWh (0 to capacity)
     * @param  int     $kwhPrice       charging price per kWh, in rupiah
     * @return int  charging price in rupiah (0 outside electric garages)
     */
    public function chargeCost(string $garageType, float $capacity, float $currentCharge, int $kwhPrice): int
    {
        if ($garageType !== 'electric') {
            return 0;
        }

        return (int) round(($capacity - $currentCharge) * $kwhPrice);
    }

    /**
     * Price of moving a rental's end date later, from $oldEnd to $newEnd.
     * Each whole month (counted by the days of that month) costs the monthly
     * price; leftover days cost the daily price each.
     * Example: 30 Nov -> 4 Jan = December (1 month) + 4 days.
     *
     * @param  int     $dailyPrice  garage price per day, in rupiah
     * @param  Carbon  $oldEnd      current end date
     * @param  Carbon  $newEnd      new end date (after $oldEnd)
     * @return int  extra price in rupiah
     */
    public function extensionCost(int $dailyPrice, Carbon $oldEnd, Carbon $newEnd): int
    {
        $remainingDays = (int) $oldEnd->diffInDays($newEnd);
        $cursor = $oldEnd->copy()->addDay();   // first extra day
        $cost = 0;

        while ($remainingDays >= $cursor->daysInMonth) {
            $monthDays = $cursor->daysInMonth;
            $cost += $dailyPrice * self::MONTHLY_MULTIPLIER;
            $remainingDays -= $monthDays;
            $cursor->addDays($monthDays);
        }

        return $cost + $remainingDays * $dailyPrice;
    }
}
