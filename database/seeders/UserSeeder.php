<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Test Admin', 'email' => 'admin@example.com', 'role' => 'ADMIN'],
            ['name' => 'Test Manager', 'email' => 'manager@example.com', 'role' => 'MANAGER'],
            ['name' => 'Test Finance', 'email' => 'finance@example.com', 'role' => 'FINANCE'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
