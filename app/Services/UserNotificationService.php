<?php

namespace App\Services;

use App\Models\UserNotification;

class UserNotificationService
{
    public static function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $meta = []
    ): void {
        UserNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'meta' => $meta,
        ]);
    }
}
