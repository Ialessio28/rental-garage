<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Create the admin account (email: admin@garage.test, password: password).
     */
    public function run(): void
    {
        User::create([
            'nik' => '0000000000000000',
            'name' => 'Admin',
            'phone' => '000000000000',
            'email' => 'admin@garage.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'approved',
        ]);
    }
}
