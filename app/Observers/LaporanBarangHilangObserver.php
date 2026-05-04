<?php

namespace App\Observers;

use App\Models\LaporanBarangHilang;
use App\Services\AdminNotificationService;

class LaporanBarangHilangObserver
{
    public function created(LaporanBarangHilang $laporan): void
    {
        if (($laporan->sumber_laporan ?? 'lapor_hilang') !== 'lapor_hilang') {
            return;
        }

        if ($laporan->region_id) {
            AdminNotificationService::notifyAdminsByRegion(
                regionId: (int) $laporan->region_id,
                type: 'laporan_hilang_baru',
                title: 'Laporan baru',
                message: 'Barang hilang dilaporkan: '.$laporan->nama_barang.' di '.$laporan->lokasi_hilang,
                actionUrl: route('admin.lost-items'),
                meta: ['laporan_id' => $laporan->id]
            );

            return;
        }

        AdminNotificationService::notifyAllAdmins(
            type: 'laporan_hilang_baru',
            title: 'Laporan baru',
            message: 'Barang hilang dilaporkan: '.$laporan->nama_barang.' di '.$laporan->lokasi_hilang,
            actionUrl: route('admin.lost-items'),
            meta: ['laporan_id' => $laporan->id]
        );
    }
}
