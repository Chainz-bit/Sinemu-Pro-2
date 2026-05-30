<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $fillable = ['nama_kategori'];

    public function scopeForForm($query)
    {
        return $query
            ->whereRaw('LOWER(nama_kategori) <> ?', ['tas'])
            ->orderByRaw("CASE WHEN LOWER(nama_kategori) = 'lainnya' THEN 1 ELSE 0 END")
            ->orderBy('nama_kategori');
    }

    public function barangs()
    {
        return $this->hasMany(Barang::class);
    }
}
