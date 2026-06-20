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
        User::updateOrCreate(
            ['email' => 'admin@eightfinity.com'],
            [
                'name' => 'EightFinity Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@eightfinity.com'],
            [
                'name' => 'EightFinity User',
                'password' => Hash::make('User@12345'),
                'role' => 'user',
            ],
        );
    }
}
