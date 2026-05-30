<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('name') !== null ? trim((string) $this->input('name')) : null,
            'email' => $this->input('email') !== null ? trim((string) $this->input('email')) : null,
            'username' => $this->input('username') !== null ? trim((string) $this->input('username')) : null,
            'phone' => $this->input('phone') !== null ? trim((string) $this->input('phone')) : null,
            'alamat' => $this->input('alamat') !== null ? trim((string) $this->input('alamat')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = (int) ($this->user()?->id ?? 0);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
