<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Elektronik',
            'Dokumen',
            'Aksesoris',
            'Kendaraan',
            'Pakaian',
            'Perhiasan',
            'Lainnya',
        ];

        foreach ($categories as $name) {
            Kategori::query()->firstOrCreate([
                'nama_kategori' => $name,
            ]);
        }
    }
}
