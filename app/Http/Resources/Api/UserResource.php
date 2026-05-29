<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) ($this->name ?? $this->nama ?? ''),
            'email' => (string) $this->email,
            'username' => $this->username,
            'phone' => $this->nomor_telepon,
            'alamat' => $this->alamat,
            'avatar' => $this->avatar ?? null,
        ];
    }
}
