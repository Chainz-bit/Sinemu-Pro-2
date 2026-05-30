<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperNotificationDismissal extends Model
{
    protected $fillable = [
        'super_admin_id',
        'item_key',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }
}
