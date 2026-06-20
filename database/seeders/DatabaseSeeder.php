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
        $adminEmail = env('ADMIN_SEED_EMAIL', 'admin@eightfinity.com');
        $adminPassword = env('ADMIN_SEED_PASSWORD', 'Admin@12345');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_SEED_NAME', 'EightFinity Admin'),
                'phone' => env('ADMIN_SEED_PHONE', '+6280000000000'),
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
            ],
        );

        if (! app()->isProduction()) {
            User::updateOrCreate(
                ['email' => 'user@eightfinity.com'],
                [
                    'name' => 'EightFinity User',
                    'phone' => '+6281111111111',
                    'password' => Hash::make('User@12345'),
                    'role' => 'user',
                ],
            );
        }
    }
}
