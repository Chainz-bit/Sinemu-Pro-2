<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminNotification;

class AdminNotificationService
{
    public static function notifyAdmin(
        int $adminId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $meta = []
    ): void {
        AdminNotification::query()->create([
            'admin_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'meta' => $meta,
        ]);
    }

    public static function notifyAllAdmins(
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $meta = []
    ): void {
        Admin::query()->select('id')->lazy()->each(function ($admin) use ($type, $title, $message, $actionUrl, $meta) {
            self::notifyAdmin((int) $admin->id, $type, $title, $message, $actionUrl, $meta);
        });
    }
}
