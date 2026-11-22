<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLACK BOX tests (through the web pages, checking only what the user sees)
 * plus GREY BOX checks on the database.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_validData_savedAsPendingTenant(): void
    {
        $this->post('/register', [
            'nik' => '3578010101010001',
            'name' => 'Budi',
            'phone' => '081234567890',
            'email' => 'budi@mail.com',
            'password' => 'password123',
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'budi@mail.com', 'role' => 'tenant', 'status' => 'pending']);
    }

    public function test_register_nikNot16Digits_rejected(): void
    {
        $this->post('/register', [
            'nik' => '12345',
            'name' => 'Budi',
            'phone' => '081234567890',
            'email' => 'budi@mail.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('nik');
    }

    public function test_login_pendingTenant_rejected(): void
    {
        $tenant = User::factory()->pending()->create();

        $this->post('/login', ['email' => $tenant->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_afterAdminApproves_succeeds(): void
    {
        $admin = User::factory()->admin()->create();
        $tenant = User::factory()->pending()->create();

        $this->actingAs($admin)->post("/admin/tenants/{$tenant->id}/approve");
        $this->post('/logout');

        $this->post('/login', ['email' => $tenant->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($tenant);
    }

    public function test_adminPage_openedByTenant_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/garages')->assertForbidden();
    }
}
