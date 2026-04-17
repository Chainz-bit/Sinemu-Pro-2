<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $laporan_hilang_id
 * @property int|null $barang_id
 * @property int|null $pencocokan_id
 * @property int $user_id
 * @property int|null $admin_id
 * @property string $status_klaim
 * @property string|null $status_verifikasi
 * @property string|null $catatan
 * @property array|null $bukti_foto
 * @property string|null $bukti_ciri_khusus
 * @property string|null $bukti_detail_isi
 * @property string|null $bukti_lokasi_spesifik
 * @property string|null $bukti_waktu_hilang
 * @property array|null $hasil_checklist
 * @property float|null $skor_validitas
 * @property string|null $catatan_verifikasi_admin
 * @property string|null $alasan_penolakan
 * @property string|null $diverifikasi_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read LaporanBarangHilang|null $laporanHilang
 * @property-read Barang|null $barang
 * @property-read Pencocokan|null $pencocokan
 * @property-read User $user
 * @property-read Admin|null $admin
 */
class Klaim extends Model
{
    protected $fillable = [
        'laporan_hilang_id',
        'barang_id',
        'pencocokan_id',
        'user_id',
        'admin_id',
        'status_klaim',
        'status_verifikasi',
        'catatan',
        'bukti_foto',
        'bukti_ciri_khusus',
        'bukti_detail_isi',
        'bukti_lokasi_spesifik',
        'bukti_waktu_hilang',
        'hasil_checklist',
        'skor_validitas',
        'catatan_verifikasi_admin',
        'alasan_penolakan',
        'diverifikasi_at',
    ];

    protected $casts = [
        'bukti_foto' => 'array',
        'hasil_checklist' => 'array',
        'diverifikasi_at' => 'datetime',
    ];

    public function laporanHilang()
    {
        return $this->belongsTo(LaporanBarangHilang::class, 'laporan_hilang_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function pencocokan()
    {
        return $this->belongsTo(Pencocokan::class, 'pencocokan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
