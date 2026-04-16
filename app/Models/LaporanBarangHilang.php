<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanBarangHilang extends Model
{
    protected $fillable = [
        'user_id',
        'nama_barang',
        'kategori_barang',
        'warna_barang',
        'merek_barang',
        'nomor_seri',
        'lokasi_hilang',
        'detail_lokasi_hilang',
        'tanggal_hilang',
        'waktu_hilang',
        'keterangan',
        'ciri_khusus',
        'kontak_pelapor',
        'bukti_kepemilikan',
        'foto_barang',
        'sumber_laporan',
        'tampil_di_home',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function klaims()
    {
        return $this->hasMany(Klaim::class, 'laporan_hilang_id');
    }
}
