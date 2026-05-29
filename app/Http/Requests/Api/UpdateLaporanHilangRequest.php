<?php

namespace App\Http\Requests\Api;

use App\Rules\RegionHasActiveAdmin;

class UpdateLaporanHilangRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_barang' => ['sometimes', 'required', 'string', 'max:255'],
            'kategori_id' => ['sometimes', 'required', 'integer', 'exists:kategoris,id'],
            'wilayah_id' => ['sometimes', 'required', 'integer', 'exists:wilayahs,id', new RegionHasActiveAdmin],
            'lokasi' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'required', 'string'],
            'tanggal_hilang' => ['sometimes', 'required', 'date'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
