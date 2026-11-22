<?php

namespace Tests\Unit;

use App\Services\RentalCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * WHITE BOX unit tests for RentalCalculator.
 * Each test targets a specific branch or loop in the calculator's code.
 * Test names follow: method_condition_expectedResult.
 */
class RentalCalculatorTest extends TestCase
{
    private RentalCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RentalCalculator();
    }

    // ---------- allowedGarageTypes ----------

    public function test_allowedGarageTypes_electricCar_returnsCarAndElectric(): void
    {
        // Act
        $types = $this->calculator->allowedGarageTypes('electric_car');

        // Assert
        $this->assertSame(['car', 'electric'], $types);
    }

    public function test_allowedGarageTypes_motorcycle_returnsBicycleOnly(): void
    {
        $this->assertSame(['bicycle'], $this->calculator->allowedGarageTypes('motorcycle'));
    }

    public function test_allowedGarageTypes_unknownVehicle_returnsEmpty(): void
    {
        $this->assertSame([], $this->calculator->allowedGarageTypes('truck'));
    }

    // ---------- maxDailyDays ----------

    public function test_maxDailyDays_november_returns29(): void
    {
        // Arrange
        $start = Carbon::parse('2026-11-21');

        // Act
        $max = $this->calculator->maxDailyDays($start);

        // Assert
        $this->assertSame(29, $max);
    }

    public function test_maxDailyDays_february_returns27(): void
    {
        $this->assertSame(27, $this->calculator->maxDailyDays(Carbon::parse('2026-02-10')));
    }

    public function test_maxDailyDays_februaryLeapYear_returns28(): void
    {
        $this->assertSame(28, $this->calculator->maxDailyDays(Carbon::parse('2028-02-10')));
    }

    // ---------- rentalDays and endDate ----------

    public function test_rentalDays_monthlyInFebruary_returns28(): void
    {
        $this->assertSame(28, $this->calculator->rentalDays(Carbon::parse('2026-02-10'), 'monthly', null));
    }

    public function test_rentalDays_daily_returnsChosenDays(): void
    {
        $this->assertSame(5, $this->calculator->rentalDays(Carbon::parse('2026-11-21'), 'daily', 5));
    }

    public function test_endDate_oneDay_endsOnStartDate(): void
    {
        // Boundary: the shortest rental
        $this->assertSame('2026-11-21', $this->calculator->endDate(Carbon::parse('2026-11-21'), 1)->toDateString());
    }

    public function test_endDate_thirtyDays_endsNextMonth(): void
    {
        // Arrange
        $start = Carbon::parse('2026-11-21');

        // Act
        $end = $this->calculator->endDate($start, 30);

        // Assert
        $this->assertSame('2026-12-20', $end->toDateString());
        $this->assertSame('2026-11-21', $start->toDateString()); // the start date is not changed
    }

    // ---------- rentCost ----------

    public function test_rentCost_monthly_returns25TimesDailyPrice(): void
    {
        $this->assertSame(250000, $this->calculator->rentCost(10000, 'monthly', 30));
    }

    public function test_rentCost_monthlyInFebruary_returnsSamePrice(): void
    {
        $this->assertSame(250000, $this->calculator->rentCost(10000, 'monthly', 28));
    }

    public function test_rentCost_daily_returnsDaysTimesDailyPrice(): void
    {
        $this->assertSame(50000, $this->calculator->rentCost(10000, 'daily', 5));
    }

    // ---------- chargeCost ----------

    public function test_chargeCost_electricGarage_paysOnlyMissingKwh(): void
    {
        // Arrange: 100 kWh battery holding 60 kWh, Rp 2,000 per kWh
        $capacity = 100;
        $currentCharge = 60;

        // Act
        $cost = $this->calculator->chargeCost('electric', $capacity, $currentCharge, 2000);

        // Assert: 40 kWh x 2,000
        $this->assertSame(80000, $cost);
    }

    public function test_chargeCost_batteryAlreadyFull_returnsZero(): void
    {
        // Boundary: current charge = capacity
        $this->assertSame(0, $this->calculator->chargeCost('electric', 100, 100, 2000));
    }

    public function test_chargeCost_batteryEmpty_paysFullCapacity(): void
    {
        // Boundary: current charge = 0
        $this->assertSame(200000, $this->calculator->chargeCost('electric', 100, 0, 2000));
    }

    public function test_chargeCost_carGarage_returnsZero(): void
    {
        $this->assertSame(0, $this->calculator->chargeCost('car', 100, 60, 2000));
    }

    // ---------- extensionCost ----------

    public function test_extensionCost_fewDays_usesDailyPrice(): void
    {
        // 20 Dec -> 25 Dec = 5 extra days (the loop does not run)
        $cost = $this->calculator->extensionCost(10000, Carbon::parse('2026-12-20'), Carbon::parse('2026-12-25'));

        $this->assertSame(50000, $cost);
    }

    public function test_extensionCost_monthPlusDays_usesMonthlyThenDailyPrice(): void
    {
        // 30 Nov -> 4 Jan = 35 days: December (31 days) as 1 month + 4 days (the loop runs once)
        $cost = $this->calculator->extensionCost(10000, Carbon::parse('2026-11-30'), Carbon::parse('2027-01-04'));

        $this->assertSame(250000 + 40000, $cost);
    }

    public function test_extensionCost_oneDayShortOfMonth_usesDailyPriceOnly(): void
    {
        // Boundary: 30 Nov -> 30 Dec = 30 days, December has 31, so no month is counted
        $cost = $this->calculator->extensionCost(10000, Carbon::parse('2026-11-30'), Carbon::parse('2026-12-30'));

        $this->assertSame(300000, $cost);
    }

    public function test_extensionCost_oneDay_isPositive(): void
    {
        $cost = $this->calculator->extensionCost(10000, Carbon::parse('2026-12-20'), Carbon::parse('2026-12-21'));

        $this->assertSame(10000, $cost);
    }
}
