<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\Concerns\FormatsApiValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KlaimResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'barang_id' => $this->barang_id !== null ? (int) $this->barang_id : null,
            'nama_barang' => (string) ($this->barang?->nama_barang ?? $this->laporanHilang?->nama_barang ?? 'Klaim Barang'),
            'status' => (string) $this->status_klaim,
            'alasan' => $this->catatan,
            'kontak' => $this->kontak ?? $this->laporanHilang?->kontak_pelapor ?? $this->user?->nomor_telepon,
            'tanggal' => $this->formatDateValue($this->created_at),
        ];
    }
}
