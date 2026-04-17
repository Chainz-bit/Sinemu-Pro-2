<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super = SuperAdmin::updateOrCreate(
            ['email' => 'superadmin@sinemu.com'],
            [
                'nama' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('super123'),
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'admin@sinemu.local'],
            [
                'super_admin_id' => $super->id,
                'nama' => 'Angga Pengelola Sistem',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'instansi' => 'Politeknik Negeri Indramayu',
                'kecamatan' => 'Indramayu Kota',
                'alamat_lengkap' => 'Jl. Jenderal Sudirman No. 88, Indramayu',
                'status_verifikasi' => 'active',
                'verified_at' => now(),
            ]
        );
    }
}
