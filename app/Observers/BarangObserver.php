<?php

namespace App\Observers;

use App\Models\Barang;
use App\Services\AdminNotificationService;

class BarangObserver
{
    public function created(Barang $barang): void
    {
        AdminNotificationService::notifyAdmin(
            adminId: (int) $barang->admin_id,
            type: 'barang_temuan_baru',
            title: 'Barang temuan baru',
            message: 'Data barang temuan ditambahkan: '.$barang->nama_barang,
            actionUrl: route(\App\Support\ManagerPortal::routeName('found-items')),
            meta: ['barang_id' => $barang->id]
        );
    }
}
