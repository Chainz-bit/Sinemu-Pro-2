<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pencocokan extends Model
{
    protected $fillable = [
        'laporan_hilang_id',
        'barang_id',
        'admin_id',
        'status_pencocokan',
        'catatan',
        'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_at' => 'datetime',
        ];
    }

    public function laporanHilang()
    {
        return $this->belongsTo(LaporanBarangHilang::class, 'laporan_hilang_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function klaims()
    {
        return $this->hasMany(Klaim::class, 'pencocokan_id');
    }
}
