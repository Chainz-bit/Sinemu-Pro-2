<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotifikasiResource;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotifikasiController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $notifications = request()->user()
            ->notifications()
            ->latest('created_at')
            ->get();

        return NotifikasiResource::collection($notifications);
    }

    public function markAsRead(UserNotification $notification): JsonResponse
    {
        if ((int) $notification->user_id !== (int) request()->user()->id) {
            abort(403, 'Tidak punya akses untuk data ini.');
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notifikasi ditandai sudah dibaca',
            'data' => new NotifikasiResource($notification->refresh()),
        ]);
    }
}
