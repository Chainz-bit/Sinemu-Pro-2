<?php

namespace Database\Seeders;

use App\Models\LaporanBarangHilang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BarangHilangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User Biasa',
                'nama' => 'User Biasa',
                'username' => 'user',
                'password' => Hash::make('password'),
            ]
        );

        $lostItems = [
            [
                'nama_barang' => 'Dompet Kulit Coklat',
                'lokasi_hilang' => 'Alun-Alun Indramayu',
                'tanggal_hilang' => '2026-03-28',
                'keterangan' => 'Berisi KTP, SIM, dan kartu ATM.',
            ],
            [
                'nama_barang' => 'Kunci Motor Honda',
                'lokasi_hilang' => 'Pasar Jatibarang',
                'tanggal_hilang' => '2026-03-30',
                'keterangan' => 'Gantungan kunci warna merah.',
            ],
            [
                'nama_barang' => 'Tas Ransel Hitam',
                'lokasi_hilang' => 'Terminal Sindang',
                'tanggal_hilang' => '2026-04-02',
                'keterangan' => 'Berisi laptop dan dokumen kerja.',
            ],
        ];

        foreach ($lostItems as $item) {
            $payload = [
                'tanggal_hilang' => $item['tanggal_hilang'],
                'keterangan' => $item['keterangan'],
            ];

            if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
                $payload['sumber_laporan'] = 'lapor_hilang';
            }

            LaporanBarangHilang::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'nama_barang' => $item['nama_barang'],
                    'lokasi_hilang' => $item['lokasi_hilang'],
                ],
                $payload
            );
        }
    }
}
