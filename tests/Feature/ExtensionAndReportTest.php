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
 * BLACK BOX tests for "Extend Rental" and "Reports", with GREY BOX database checks.
 */
class ExtensionAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-21 10:00:00');
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();

        // One daily car rental: 21-23 Nov, Rp 20,000/day -> Rp 60,000
        Garage::createBatch('car', 1, 20000, null);
        $this->actingAs(User::factory()->create(['name' => 'Budi']))->post('/rentals', [
            'choice' => 'car:car',
            'start_date' => '2026-11-21',
            'garage_id' => Garage::first()->id,
            'plate_number' => 'L 1234 AB',
            'brand_model' => 'Toyota Avanza',
            'rental_type' => 'daily',
            'days' => 3,
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $this->post('/logout');
    }

    public function test_extendForm_newEndDate_showsRemainingCost(): void
    {
        $rental = Rental::first();

        $this->actingAs($this->admin)
            ->get("/admin/extensions/{$rental->id}?new_end_date=2026-11-25")
            ->assertOk()
            ->assertSee('Rp 40.000');   // 24 and 25 Nov = 2 x 20,000
    }

    public function test_extend_twoDays_endDateAndTotalUpdated(): void
    {
        $rental = Rental::first();

        $this->actingAs($this->admin)->post("/admin/extensions/{$rental->id}", [
            'new_end_date' => '2026-11-25',
            'payment_proof' => UploadedFile::fake()->image('extra.jpg'),
        ])->assertSessionHasNoErrors();

        $rental->refresh();
        $this->assertSame('2026-11-25', $rental->end_date->toDateString());
        $this->assertSame(100000, $rental->total_cost);   // 60,000 + 40,000
        $this->assertDatabaseHas('extensions', ['rental_id' => $rental->id, 'extra_cost' => 40000]);
    }

    public function test_extend_earlierEndDate_rejected(): void
    {
        $rental = Rental::first();

        $this->actingAs($this->admin)->post("/admin/extensions/{$rental->id}", [
            'new_end_date' => '2026-11-22',
            'payment_proof' => UploadedFile::fake()->image('extra.jpg'),
        ])->assertSessionHasErrors('new_end_date');
    }

    public function test_extend_intoAnotherBooking_rejected(): void
    {
        // Another tenant booked the same garage from 25 Nov, so extending to 25 Nov is rejected
        $rental = Rental::first();
        Rental::create($rental->only([
            'garage_id', 'vehicle_type', 'plate_number', 'brand_model', 'rental_type', 'days',
            'rent_cost', 'charge_cost', 'total_cost', 'payment_proof',
        ]) + ['user_id' => User::factory()->create()->id, 'start_date' => '2026-11-25', 'end_date' => '2026-11-27']);

        $this->actingAs($this->admin)->post("/admin/extensions/{$rental->id}", [
            'new_end_date' => '2026-11-25',
            'payment_proof' => UploadedFile::fake()->image('extra.jpg'),
        ])->assertSessionHasErrors('new_end_date');

        $this->assertSame('2026-11-23', $rental->refresh()->end_date->toDateString());
    }

    public function test_reports_allFourTypes_showTotals(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports?type=garage_type')
            ->assertOk()->assertSee('Car')->assertSee('Rp 60.000');

        $this->actingAs($this->admin)->get('/admin/reports?type=tenant')
            ->assertOk()->assertSee('Budi');

        $this->actingAs($this->admin)->get('/admin/reports?type=month&year=2026')
            ->assertOk()->assertSee('November 2026');

        $this->actingAs($this->admin)->get('/admin/reports?type=day&year=2026&month=11')
            ->assertOk()->assertSee('21 Nov 2026');
    }
}
