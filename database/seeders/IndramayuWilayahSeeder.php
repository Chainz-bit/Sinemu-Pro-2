<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use App\Support\IndramayuDistricts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class IndramayuWilayahSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('wilayahs')) {
            return;
        }

        foreach (IndramayuDistricts::wilayahItems() as $wilayah) {
            Wilayah::firstOrCreate(
                ['nama_wilayah' => $wilayah['nama_wilayah']],
                ['lat' => $wilayah['lat'], 'lng' => $wilayah['lng']]
            );
        }
    }
}
