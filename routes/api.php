<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/tes-barang', function () {
    return response()->json([
        'status' => 'sukses',
        'pesan' => 'Halo Abang Tampan! Ini data langsung dari Dapur Laravel!',
        'data_barang' => [
            ['id' => 1, 'nama' => 'Kunci Mobil Honda', 'kategori' => 'OTOMOTIF', 'status' => 'TEMUAN'],
            ['id' => 2, 'nama' => 'Dompet Kulit Coklat', 'kategori' => 'AKSESORIS', 'status' => 'HILANG']
        ]
    ]);
});