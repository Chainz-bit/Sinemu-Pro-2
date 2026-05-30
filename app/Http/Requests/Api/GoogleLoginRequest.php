<?php

namespace App\Http\Requests\Api;

class GoogleLoginRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_token' => $this->input('id_token') !== null ? trim((string) $this->input('id_token')) : null,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => 'Token Google wajib dikirim.',
        ];
    }
}
