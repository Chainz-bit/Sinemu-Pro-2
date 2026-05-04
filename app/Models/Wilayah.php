<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $fillable = ['nama_wilayah', 'lat', 'lng'];

    public function admins()
    {
        return $this->hasMany(Admin::class, 'region_id');
    }

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'region_id');
    }

    public function laporanBarangHilangs()
    {
        return $this->hasMany(LaporanBarangHilang::class, 'region_id');
    }
}
