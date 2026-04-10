<?php

namespace App\Observers;

use App\Models\Klaim;
use App\Services\AdminNotificationService;

class KlaimObserver
{
    public function created(Klaim $klaim): void
    {
        AdminNotificationService::notifyAdmin(
            adminId: (int) $klaim->admin_id,
            type: 'klaim_baru',
            title: 'Klaim baru',
            message: 'Ada pengajuan klaim baru untuk diverifikasi.',
            actionUrl: route('admin.claim-verifications'),
            meta: ['klaim_id' => $klaim->id]
        );
    }
}
