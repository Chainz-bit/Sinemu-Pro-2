<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $admin_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $action_url
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AdminNotification extends Model
{
    protected $fillable = [
        'admin_id',
        'type',
        'title',
        'message',
        'action_url',
        'meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
