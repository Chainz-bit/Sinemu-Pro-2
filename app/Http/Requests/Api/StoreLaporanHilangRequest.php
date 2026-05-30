<?php

namespace App\Http\Requests\Api;

use App\Rules\RegionHasActiveAdmin;

class StoreLaporanHilangRequest extends ApiFormRequest
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
            'tanggal_hilang' => ['required', 'date'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'kategori_id.required' => 'Kategori wajib dipilih.',
            'kategori_id.exists' => 'Kategori tidak valid.',
            'wilayah_id.required' => 'Wilayah wajib dipilih.',
            'wilayah_id.exists' => 'Wilayah tidak valid.',
            'lokasi.required' => 'Lokasi wajib diisi.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'tanggal_hilang.required' => 'Tanggal hilang wajib diisi.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat jpg, jpeg, atau png.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
