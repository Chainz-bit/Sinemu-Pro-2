<?php

namespace App\Support;

class AdminVerificationStatusPresenter
{
    public static function key(?string $status): string
    {
        return match (trim((string) $status)) {
            'active' => 'active',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    public static function label(?string $status): string
    {
        return match (self::key($status)) {
            'active' => 'Aktif',
            'rejected' => 'Ditolak',
            default => 'Menunggu Verifikasi',
        };
    }

    public static function badgeClass(?string $status): string
    {
        return match (self::key($status)) {
            'active' => 'status-selesai',
            'rejected' => 'status-ditolak',
            default => 'status-diproses',
        };
    }

    public static function cardClass(?string $status): string
    {
        return match (self::key($status)) {
            'active' => 'stat-card-found',
            'rejected' => 'stat-card-lost',
            default => 'stat-card-claim',
        };
    }

    public static function description(?string $status): string
    {
        return match (self::key($status)) {
            'active' => 'Admin sudah bisa mengakses dashboard dan mengelola laporan.',
            'rejected' => 'Pendaftaran ditolak dan menunggu perbaikan data oleh pendaftar.',
            default => 'Admin menunggu tinjauan dan keputusan super admin.',
        };
    }
}
