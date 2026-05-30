<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\Concerns\FormatsApiValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotifikasiResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'judul' => (string) $this->title,
            'pesan' => (string) $this->message,
            'tanggal' => $this->formatDateValue($this->created_at),
            'dibaca' => $this->read_at !== null,
        ];
    }
}
