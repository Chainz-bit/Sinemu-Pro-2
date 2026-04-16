<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const DASHBOARD_CACHE_TTL_SECONDS = 30;

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $userId = (int) $user->id;

        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', 'semua'));
        $hasSourceColumn = Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan');

        // BAGIAN: Statistik ringkas user.
        [$totalLaporHilang, $totalPengajuanKlaim, $menungguVerifikasi] = Cache::remember(
            $this->statsCacheKey($userId),
            now()->addSeconds(self::DASHBOARD_CACHE_TTL_SECONDS),
            function () use ($userId, $hasSourceColumn) {
                $lostReportsQuery = LaporanBarangHilang::query()->where('user_id', $userId);
                if ($hasSourceColumn) {
                    $lostReportsQuery->where('sumber_laporan', 'lapor_hilang');
                }

                $totalLaporHilang = (clone $lostReportsQuery)->count();
                $totalPengajuanKlaim = Klaim::query()->where('user_id', $userId)->count();
                $menungguVerifikasi = Klaim::query()
                    ->where('user_id', $userId)
                    ->where('status_klaim', 'pending')
                    ->count();

                return [$totalLaporHilang, $totalPengajuanKlaim, $menungguVerifikasi];
            }
        );

        // BAGIAN: Aktivitas terbaru user (laporan hilang + klaim).
        $latestActivities = Cache::remember(
            $this->activitiesCacheKey($userId),
            now()->addSeconds(self::DASHBOARD_CACHE_TTL_SECONDS),
            fn () => $this->buildLatestActivities($userId, $hasSourceColumn)
        );
        $latestActivities = $this->filterLatestActivities($latestActivities, $search, $statusFilter);
        $latestActivities = $this->paginateItems(
            $latestActivities,
            max((int) $request->query('page', 1), 1),
            8
        );

        return view('user.pages.dashboard', [
            'user' => $user,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalLaporHilang' => $totalLaporHilang,
            'totalPengajuanKlaim' => $totalPengajuanKlaim,
            'menungguVerifikasi' => $menungguVerifikasi,
            'latestActivities' => $latestActivities,
        ]);
    }

    private function buildLatestActivities(int $userId, bool $hasSourceColumn): Collection
    {
        $lostReports = LaporanBarangHilang::query()
            ->where('user_id', $userId)
            ->when($hasSourceColumn, function ($query) {
                $query->where('sumber_laporan', 'lapor_hilang');
            })
            ->select(['id', 'nama_barang', 'lokasi_hilang', 'tanggal_hilang', 'created_at', 'updated_at'])
            ->selectSub(
                Klaim::query()
                    ->whereColumn('laporan_hilang_id', 'laporan_barang_hilangs.id')
                    ->where('user_id', $userId)
                    ->latest('updated_at')
                    ->limit(1)
                    ->select('status_klaim'),
                'latest_claim_status'
            )
            ->latest('updated_at')
            ->limit(16)
            ->get()
            ->map(function (LaporanBarangHilang $report) {
                $statusPayload = match ((string) $report->latest_claim_status) {
                    'pending' => ['status' => 'dalam_peninjauan', 'status_class' => 'status-dalam_peninjauan', 'status_text' => 'MENUNGGU VERIFIKASI'],
                    'disetujui' => ['status' => 'selesai', 'status_class' => 'status-selesai', 'status_text' => 'SELESAI'],
                    'ditolak' => ['status' => 'ditolak', 'status_class' => 'status-ditolak', 'status_text' => 'DITOLAK'],
                    default => ['status' => 'diproses', 'status_class' => 'status-diproses', 'status_text' => 'TERKIRIM'],
                };

                $activityAt = strtotime((string) ($report->updated_at ?? $report->created_at));

                return (object) [
                    'type' => 'lost_report',
                    'report_id' => (int) $report->id,
                    'item_name' => (string) $report->nama_barang,
                    'item_detail' => 'Laporan Hilang - ' . (string) $report->lokasi_hilang,
                    'incident_date' => (string) $report->tanggal_hilang,
                    'created_at' => $report->created_at,
                    'activity_at' => $activityAt,
                    'status' => $statusPayload['status'],
                    'status_class' => $statusPayload['status_class'],
                    'status_text' => $statusPayload['status_text'],
                    'avatar' => 'H',
                    'avatar_class' => 'avatar-sand',
                    'detail_url' => $this->resolveActionUrl('lost_report', $statusPayload['status'], (int) $report->id),
                    'action_label' => $this->resolveActionLabel('lost_report', $statusPayload['status']),
                    'can_delete' => $this->canDeleteLostReport($statusPayload['status']),
                    'delete_url' => route('user.lost-reports.destroy', $report->id),
                ];
            });

        $claims = Klaim::query()
            ->where('user_id', $userId)
            ->with([
                'barang:id,nama_barang,lokasi_ditemukan',
                'laporanHilang:id,nama_barang,lokasi_hilang',
            ])
            ->select(['id', 'status_klaim', 'created_at', 'updated_at', 'barang_id', 'laporan_hilang_id'])
            ->latest('updated_at')
            ->limit(16)
            ->get()
            ->map(function (Klaim $claim) {
                $statusPayload = match ((string) $claim->status_klaim) {
                    'disetujui' => ['status' => 'selesai', 'status_class' => 'status-selesai', 'status_text' => 'DISETUJUI'],
                    'ditolak' => ['status' => 'ditolak', 'status_class' => 'status-ditolak', 'status_text' => 'DITOLAK'],
                    default => ['status' => 'dalam_peninjauan', 'status_class' => 'status-dalam_peninjauan', 'status_text' => 'MENUNGGU VERIFIKASI'],
                };

                $itemName = (string) ($claim->barang?->nama_barang ?? $claim->laporanHilang?->nama_barang ?? 'Klaim Barang');
                $location = (string) ($claim->barang?->lokasi_ditemukan ?? $claim->laporanHilang?->lokasi_hilang ?? 'Lokasi tidak tersedia');
                $activityAt = strtotime((string) ($claim->updated_at ?? $claim->created_at));

                return (object) [
                    'type' => 'claim',
                    'report_id' => null,
                    'item_name' => $itemName,
                    'item_detail' => 'Klaim Barang - ' . $location,
                    'incident_date' => (string) optional($claim->created_at)->toDateString(),
                    'created_at' => $claim->created_at,
                    'activity_at' => $activityAt,
                    'status' => $statusPayload['status'],
                    'status_class' => $statusPayload['status_class'],
                    'status_text' => $statusPayload['status_text'],
                    'avatar' => 'K',
                    'avatar_class' => 'avatar-claim',
                    'detail_url' => $this->resolveActionUrl('claim', $statusPayload['status']),
                    'action_label' => $this->resolveActionLabel('claim', $statusPayload['status']),
                    'can_delete' => false,
                    'delete_url' => null,
                ];
            });

        return $lostReports
            ->merge($claims)
            ->sortByDesc('activity_at')
            ->values();
    }

    private function filterLatestActivities(Collection $items, string $search, string $statusFilter): Collection
    {
        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $items = $items->filter(function ($item) use ($keyword) {
                $haystack = mb_strtolower(
                    trim(
                        implode(' ', [
                            (string) ($item->item_name ?? ''),
                            (string) ($item->item_detail ?? ''),
                            (string) ($item->status ?? ''),
                            (string) ($item->status_text ?? ''),
                        ])
                    )
                );

                return str_contains($haystack, $keyword);
            });
        }

        if ($statusFilter !== '' && $statusFilter !== 'semua') {
            $items = $items->filter(function ($item) use ($statusFilter) {
                return (string) ($item->status ?? '') === $statusFilter;
            });
        }

        return $items->values();
    }

    private function paginateItems(Collection $items, int $page, int $perPage): LengthAwarePaginator
    {
        $currentPageItems = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function resolveActionLabel(string $type, string $status): string
    {
        if ($type === 'claim') {
            return match ($status) {
                'ditolak' => 'Perbaiki Data',
                'selesai' => 'Lihat Hasil',
                'dalam_peninjauan' => 'Lihat Status',
                default => 'Lihat Detail',
            };
        }

        return match ($status) {
            'diproses' => 'Edit Laporan',
            'dalam_peninjauan' => 'Lihat Status',
            'ditolak' => 'Perbaiki Data',
            'selesai' => 'Lihat Hasil',
            default => 'Lihat Detail',
        };
    }

    private function resolveActionUrl(string $type, string $status, ?int $reportId = null): string
    {
        if ($type === 'claim') {
            return match ($status) {
                'ditolak' => route('user.lost-reports.create'),
                default => route('home') . '#hilang-temuan',
            };
        }

        return match ($status) {
            'diproses', 'ditolak' => route('user.lost-reports.create', ['edit' => $reportId]),
            default => route('home') . '#hilang-temuan',
        };
    }

    private function canDeleteLostReport(string $status): bool
    {
        return $status === 'diproses';
    }

    private function statsCacheKey(int $userId): string
    {
        return 'user_dashboard:stats:' . $userId;
    }

    private function activitiesCacheKey(int $userId): string
    {
        return 'user_dashboard:activities:' . $userId;
    }
}
