<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User Biasa',
                'nama' => 'User Biasa',
                'username' => 'user',
                'nomor_telepon' => '081234567890',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
    }
}
