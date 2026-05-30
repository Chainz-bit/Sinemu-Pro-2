<?php

namespace App\Http\Requests\Api;

use App\Rules\RegionHasActiveAdmin;

class StoreLaporanTemuanRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'wilayah_id' => ['required', 'integer', 'exists:wilayahs,id', new RegionHasActiveAdmin],
            'lokasi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tanggal_ditemukan' => ['required', 'date'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
