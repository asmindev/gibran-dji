<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@inventory.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create additional demo user
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@inventory.com',
            'password' => Hash::make('demo123'),
            'email_verified_at' => now(),
        ]);
    }
}
