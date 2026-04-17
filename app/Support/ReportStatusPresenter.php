<?php

namespace App\Support;

final class ReportStatusPresenter
{
    public static function key(?string $statusLaporan): string
    {
        return (string) ($statusLaporan ?? WorkflowStatus::REPORT_SUBMITTED);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            WorkflowStatus::REPORT_APPROVED => 'DISETUJUI',
            WorkflowStatus::REPORT_REJECTED => 'DITOLAK',
            WorkflowStatus::REPORT_MATCHED => 'SEDANG DICOCOKKAN',
            WorkflowStatus::REPORT_CLAIMED => 'SEDANG DIKLAIM',
            WorkflowStatus::REPORT_COMPLETED => 'SELESAI',
            default => 'MENUNGGU VERIFIKASI',
        };
    }

    public static function cssClass(string $status): string
    {
        return match ($status) {
            WorkflowStatus::REPORT_REJECTED => 'status-ditolak',
            WorkflowStatus::REPORT_COMPLETED => 'status-selesai',
            WorkflowStatus::REPORT_MATCHED, WorkflowStatus::REPORT_APPROVED => 'status-dalam_peninjauan',
            WorkflowStatus::REPORT_CLAIMED => 'status-diproses',
            default => 'status-diproses',
        };
    }

    public static function dashboardStatus(string $status): string
    {
        return match ($status) {
            WorkflowStatus::REPORT_REJECTED => 'ditolak',
            WorkflowStatus::REPORT_COMPLETED => 'selesai',
            WorkflowStatus::REPORT_CLAIMED => 'diproses',
            default => 'dalam_peninjauan',
        };
    }
}
