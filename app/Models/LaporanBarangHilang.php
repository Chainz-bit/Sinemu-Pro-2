<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nama_barang
 * @property string|null $kategori_barang
 * @property string|null $warna_barang
 * @property string|null $merek_barang
 * @property string|null $nomor_seri
 * @property string $lokasi_hilang
 * @property string|null $detail_lokasi_hilang
 * @property string $tanggal_hilang
 * @property string|null $waktu_hilang
 * @property string|null $keterangan
 * @property string|null $ciri_khusus
 * @property string|null $kontak_pelapor
 * @property string|null $bukti_kepemilikan
 * @property string|null $foto_barang
 * @property string|null $sumber_laporan
 * @property bool $tampil_di_home
 * @property string|null $status_laporan
 * @property int|null $verified_by_admin_id
 * @property string|null $verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Klaim> $klaims
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pencocokan> $pencocokans
 */
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
        'status_laporan',
        'verified_by_admin_id',
        'verified_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function klaims()
    {
        return $this->hasMany(Klaim::class, 'laporan_hilang_id');
    }

    public function pencocokans()
    {
        return $this->hasMany(Pencocokan::class, 'laporan_hilang_id');
    }
}
