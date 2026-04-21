<?php

namespace App\Services\Super\Notifications;

use App\Services\Super\Admins\AdminVerificationQueryService;
use App\Support\AdminVerificationStatusPresenter;
use Illuminate\Support\Collection;

class SuperTopbarNotificationService
{
    private const MAX_ITEMS = 8;

    public function __construct(
        private readonly AdminVerificationQueryService $adminVerificationQueryService
    ) {
    }

    /**
     * @return array{
     *   notifications: Collection<int, array{
     *     title: string,
     *     message: string,
     *     action_url: string,
     *     created_at: mixed,
     *     is_urgent: bool,
     *     tag: string
     *   }>,
     *   unreadCount: int
     * }
     */
    public function build(): array
    {
        $pendingAdmins = $this->adminVerificationQueryService->buildPendingPreview(self::MAX_ITEMS);
        $latestActivities = $this->adminVerificationQueryService->buildLatestActivities(self::MAX_ITEMS);

        $pendingNotifications = $pendingAdmins->map(function ($admin) {
            return [
                'title' => 'Admin menunggu verifikasi',
                'message' => sprintf(
                    '%s dari %s perlu ditinjau sekarang.',
                    (string) $admin->nama,
                    (string) ($admin->instansi ?: 'instansi belum diisi')
                ),
                'action_url' => route('super.admin-verifications.index', ['search' => $admin->nama]),
                'created_at' => $admin->created_at,
                'is_urgent' => true,
                'tag' => 'Perlu tindakan',
            ];
        });

        $activityNotifications = $latestActivities
            ->filter(function ($admin) {
                return AdminVerificationStatusPresenter::key($admin->status_verifikasi) !== 'pending';
            })
            ->map(function ($admin) {
                $statusKey = AdminVerificationStatusPresenter::key($admin->status_verifikasi);
                $statusLabel = AdminVerificationStatusPresenter::label($statusKey);
                $activityTime = $admin->verified_at ?? $admin->updated_at ?? $admin->created_at;

                return [
                    'title' => sprintf('Status admin %s', $statusLabel),
                    'message' => sprintf(
                        '%s dari %s masuk ke status %s.',
                        (string) $admin->nama,
                        (string) ($admin->instansi ?: 'instansi belum diisi'),
                        strtolower($statusLabel)
                    ),
                    'action_url' => route('super.admins.index', ['search' => $admin->nama]),
                    'created_at' => $activityTime,
                    'is_urgent' => false,
                    'tag' => 'Aktivitas',
                ];
            });

        $notifications = $pendingNotifications
            ->concat($activityNotifications)
            ->sortByDesc('created_at')
            ->take(self::MAX_ITEMS)
            ->values();

        return [
            'notifications' => $notifications,
            'unreadCount' => (int) $pendingAdmins->count(),
        ];
    }
}
