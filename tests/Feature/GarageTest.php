<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLACK BOX tests for "Create Garages", with GREY BOX database checks.
 */
class GarageTest extends TestCase
{
    use RefreshDatabase;

    public function test_createGarages_twoBatches_codesContinue(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/garages', ['type' => 'bicycle', 'count' => 3, 'daily_price' => 5000]);
        $this->actingAs($admin)->post('/admin/garages', ['type' => 'bicycle', 'count' => 2, 'daily_price' => 5000]);
        $this->actingAs($admin)->post('/admin/garages', ['type' => 'car', 'count' => 10, 'daily_price' => 20000]);

        $this->assertSame(
            ['S001', 'S002', 'S003', 'S004', 'S005'],
            Garage::where('type', 'bicycle')->orderBy('code')->pluck('code')->all()
        );
        $this->assertDatabaseHas('garages', ['code' => 'C010', 'type' => 'car']);
    }

    public function test_createGarages_electricWithoutKwhPrice_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/garages', ['type' => 'electric', 'count' => 1, 'daily_price' => 30000])
            ->assertSessionHasErrors('kwh_price');
    }

    public function test_createGarages_count1000_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/garages', ['type' => 'car', 'count' => 1000, 'daily_price' => 20000])
            ->assertSessionHasErrors('count');
    }
}
