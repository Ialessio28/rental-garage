<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BLACK BOX tests for "New Rental", with GREY BOX database checks.
 * "Today" is fixed to 21 Nov 2026 so the results never change.
 */
class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-21 10:00:00');
        Storage::fake('public');
    }

    /**
     * Post a new rental with sensible default values.
     */
    private function rent(User $tenant, array $overrides = [])
    {
        return $this->actingAs($tenant)->post('/rentals', $overrides + [
            'choice' => 'car:car',
            'start_date' => '2026-11-21',
            'garage_id' => Garage::first()->id,
            'plate_number' => 'L 1234 AB',
            'brand_model' => 'Toyota Avanza',
            'rental_type' => 'daily',
            'days' => 3,
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
    }

    public function test_rent_daily3Days_savedAndGarageUsed(): void
    {
        // Arrange
        Garage::createBatch('car', 1, 20000, null);
        $tenant = User::factory()->create();

        // Act
        $this->rent($tenant)->assertSessionHasNoErrors();

        // Assert
        $rental = Rental::first();
        $this->assertSame(60000, $rental->total_cost);                     // 3 x 20,000
        $this->assertSame('2026-11-23', $rental->end_date->toDateString()); // 21, 22, 23
        $this->assertSame(0, Garage::availableBetween(today(), today())->count());   // garage is used
        Storage::disk('public')->assertExists($rental->payment_proof);
    }

    public function test_rent_monthly_costs25TimesDailyPrice(): void
    {
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['rental_type' => 'monthly', 'days' => null])->assertSessionHasNoErrors();

        $rental = Rental::first();
        $this->assertSame(500000, $rental->total_cost);
        $this->assertSame('2026-12-20', $rental->end_date->toDateString()); // November has 30 days
    }

    public function test_rent_daily30DaysInNovember_rejected(): void
    {
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['days' => 30])->assertSessionHasErrors('days');   // Nov max = 29
        $this->assertSame(0, Rental::count());
    }

    public function test_rent_daily29DaysInNovember_accepted(): void
    {
        // Boundary: exactly the maximum (30 days in November - 1)
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['days' => 29])->assertSessionHasNoErrors();

        $this->assertSame('2026-12-19', Rental::first()->end_date->toDateString());
    }

    public function test_rent_daily0Days_rejected(): void
    {
        // Boundary: below the minimum of 1 day
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['days' => 0])->assertSessionHasErrors('days');
    }

    public function test_rent_futureStartDate_savedWithThatPeriod(): void
    {
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['start_date' => '2026-12-01'])->assertSessionHasNoErrors();

        $rental = Rental::first();
        $this->assertSame('2026-12-01', $rental->start_date->toDateString());
        $this->assertSame('2026-12-03', $rental->end_date->toDateString());   // 1, 2, 3 Dec
    }

    public function test_rent_pastStartDate_rejected(): void
    {
        // Boundary: yesterday is not allowed, the start date must be today or later
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['start_date' => '2026-11-20'])->assertSessionHasErrors('start_date');
        $this->assertSame(0, Rental::count());
    }

    public function test_rent_daily30DaysStartingInDecember_accepted(): void
    {
        // The daily maximum follows the start month: December has 31 days, so 30 is allowed
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['start_date' => '2026-12-01', 'days' => 30])->assertSessionHasNoErrors();

        $this->assertSame('2026-12-30', Rental::first()->end_date->toDateString());
    }

    public function test_rent_periodOverlapsLaterBooking_rejected(): void
    {
        // Garage booked 25-27 Nov. Renting 21-25 Nov shares 25 Nov, so it is rejected.
        Garage::createBatch('car', 1, 20000, null);
        $this->rent(User::factory()->create(), ['start_date' => '2026-11-25'])->assertSessionHasNoErrors();

        $this->rent(User::factory()->create(), ['days' => 5])->assertSessionHasErrors('garage_id');
        $this->assertSame(1, Rental::count());
    }

    public function test_rent_periodEndsBeforeLaterBooking_accepted(): void
    {
        // Boundary: garage booked 25-27 Nov. Renting 21-24 Nov ends the day before, so it is allowed.
        Garage::createBatch('car', 1, 20000, null);
        $this->rent(User::factory()->create(), ['start_date' => '2026-11-25'])->assertSessionHasNoErrors();

        $this->rent(User::factory()->create(), ['days' => 4])->assertSessionHasNoErrors();
        $this->assertSame(2, Rental::count());
    }

    public function test_rent_bicycleWithoutPlate_accepted(): void
    {
        // Bicycles have no plate number
        Garage::createBatch('bicycle', 1, 5000, null);

        $this->rent(User::factory()->create(), ['choice' => 'bicycle:bicycle', 'plate_number' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull(Rental::first()->plate_number);
    }

    public function test_rent_garageAlreadyUsedToday_rejected(): void
    {
        Garage::createBatch('car', 1, 20000, null);
        $this->rent(User::factory()->create())->assertSessionHasNoErrors();

        $this->rent(User::factory()->create())->assertSessionHasErrors('garage_id');
        $this->assertSame(1, Rental::count());
    }

    public function test_rent_electricGarage_paysChargingToFull(): void
    {
        Garage::createBatch('electric', 1, 30000, 2000);

        $this->rent(User::factory()->create(), [
            'choice' => 'electric_car:electric',
            'battery_capacity' => 100,
            'current_charge' => 60,
            'days' => 1,
        ])->assertSessionHasNoErrors();

        $rental = Rental::first();
        $this->assertSame(80000, $rental->charge_cost);   // (100 - 60) x 2,000
        $this->assertSame(110000, $rental->total_cost);   // 30,000 + 80,000
    }

    public function test_rent_chargeAboveCapacity_rejected(): void
    {
        Garage::createBatch('electric', 1, 30000, 2000);

        $this->rent(User::factory()->create(), [
            'choice' => 'electric_car:electric',
            'battery_capacity' => 100,
            'current_charge' => 120,
        ])->assertSessionHasErrors('current_charge');
    }

    public function test_rent_motorcycleInCarGarage_rejected(): void
    {
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), ['choice' => 'motorcycle:car'])->assertStatus(422);
    }

    public function test_rent_proofNotImage_rejected(): void
    {
        Garage::createBatch('car', 1, 20000, null);

        $this->rent(User::factory()->create(), [
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('payment_proof');
    }
}
