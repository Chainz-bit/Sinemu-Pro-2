<?php

namespace App\Http\Requests\Api;

class StoreKlaimRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:2000'],
            'kontak' => ['nullable', 'string', 'max:50'],
        ];
    }
}
