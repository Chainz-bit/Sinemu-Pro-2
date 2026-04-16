<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserNotificationController extends Controller
{
    public function markAllAsRead(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function markAsRead(UserNotification $notification): RedirectResponse
    {
        $userId = (int) Auth::id();
        abort_if((int) $notification->user_id !== $userId, 403);

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }

    public function destroy(UserNotification $notification): RedirectResponse
    {
        $userId = (int) Auth::id();
        abort_if((int) $notification->user_id !== $userId, 403);

        $notification->delete();

        return back();
    }

    public function destroyAll(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $user->notifications()->delete();

        return back();
    }
}
