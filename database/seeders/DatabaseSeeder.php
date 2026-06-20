<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_SEED_EMAIL');
        $adminPassword = env('ADMIN_SEED_PASSWORD');

        if (
            blank($adminEmail)
            || blank($adminPassword)
            || $adminPassword === 'change-this-admin-password'
            || $adminPassword === 'your-secure-admin-password'
        ) {
            $this->command?->warn('Admin seed skipped. Set ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD in .env first.');

            return;
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_SEED_NAME', 'EightFinity Admin'),
                'phone' => env('ADMIN_SEED_PHONE', '+6280000000000'),
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
            ],
        );
    }
}
