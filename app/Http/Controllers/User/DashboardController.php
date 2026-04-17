<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $hasFoundUserColumn = Schema::hasColumn('barangs', 'user_id');

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
            fn () => $this->buildLatestActivities($userId, $hasSourceColumn, $hasFoundUserColumn)
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

    private function buildLatestActivities(int $userId, bool $hasSourceColumn, bool $hasFoundUserColumn): Collection
    {
        $lostReports = LaporanBarangHilang::query()
            ->where('user_id', $userId)
            ->when($hasSourceColumn, function ($query) {
                $query->where('sumber_laporan', 'lapor_hilang');
            })
            ->select(['id', 'nama_barang', 'lokasi_hilang', 'tanggal_hilang', 'foto_barang', 'created_at', 'updated_at'])
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
                    'image_url' => $this->resolveItemImageUrl((string) ($report->foto_barang ?? ''), 'barang-hilang'),
                    'detail_url' => $this->resolveLostReportActionUrl((int) $report->id, $statusPayload['status']),
                    'action_label' => $this->resolveActionLabel('lost_report', $statusPayload['status']),
                    'can_delete' => $this->canDeleteLostReport($statusPayload['status']),
                    'delete_url' => route('user.lost-reports.destroy', $report->id),
                ];
            });

        $foundReports = collect();
        if ($hasFoundUserColumn) {
            $foundReports = Barang::query()
                ->where('user_id', $userId)
                ->select(['id', 'nama_barang', 'lokasi_ditemukan', 'tanggal_ditemukan', 'status_barang', 'foto_barang', 'created_at', 'updated_at'])
                ->latest('updated_at')
                ->limit(16)
                ->get()
                ->map(function (Barang $item) {
                    $statusPayload = match ((string) $item->status_barang) {
                        'dalam_proses_klaim' => ['status' => 'dalam_peninjauan', 'status_class' => 'status-dalam_peninjauan', 'status_text' => 'DALAM PROSES KLAIM'],
                        'sudah_diklaim', 'sudah_dikembalikan' => ['status' => 'selesai', 'status_class' => 'status-selesai', 'status_text' => 'SELESAI'],
                        default => ['status' => 'diproses', 'status_class' => 'status-diproses', 'status_text' => 'TERKIRIM'],
                    };

                    $activityAt = strtotime((string) ($item->updated_at ?? $item->created_at));

                    return (object) [
                        'type' => 'found_report',
                        'report_id' => (int) $item->id,
                        'item_name' => (string) $item->nama_barang,
                        'item_detail' => 'Laporan Temuan - ' . (string) $item->lokasi_ditemukan,
                        'incident_date' => (string) $item->tanggal_ditemukan,
                        'created_at' => $item->created_at,
                        'activity_at' => $activityAt,
                        'status' => $statusPayload['status'],
                        'status_class' => $statusPayload['status_class'],
                        'status_text' => $statusPayload['status_text'],
                        'avatar' => 'T',
                        'avatar_class' => 'avatar-mint',
                        'image_url' => $this->resolveItemImageUrl((string) ($item->foto_barang ?? ''), 'barang-temuan'),
                        'detail_url' => route('home.found-detail', $item->id),
                        'action_label' => 'Lihat Laporan',
                        'can_delete' => false,
                        'delete_url' => null,
                    ];
                });
        }

        $claims = Klaim::query()
            ->where('user_id', $userId)
            ->with([
                'barang:id,nama_barang,lokasi_ditemukan,foto_barang',
                'laporanHilang:id,nama_barang,lokasi_hilang,foto_barang',
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
                    'image_url' => $this->resolveItemImageUrl(
                        (string) ($claim->barang?->foto_barang ?? $claim->laporanHilang?->foto_barang ?? ''),
                        $claim->barang ? 'barang-temuan' : 'barang-hilang'
                    ),
                    'detail_url' => $this->resolveClaimActionUrl($claim),
                    'action_label' => $this->resolveActionLabel('claim', $statusPayload['status']),
                    'can_delete' => false,
                    'delete_url' => null,
                ];
            });

        return $lostReports
            ->merge($foundReports)
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
                'ditolak' => 'Lihat Detail',
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

    private function resolveLostReportActionUrl(int $reportId, string $status): string
    {
        return match ($status) {
            'diproses', 'ditolak' => route('user.lost-reports.create', ['edit' => $reportId]),
            default => route('home.lost-detail', $reportId),
        };
    }

    private function resolveClaimActionUrl(Klaim $claim): string
    {
        if (!is_null($claim->barang_id)) {
            return route('home.found-detail', $claim->barang_id);
        }

        if (!is_null($claim->laporan_hilang_id)) {
            return route('home.lost-detail', $claim->laporan_hilang_id);
        }

        return route('user.claim-history');
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

    private function resolveItemImageUrl(string $fotoPath, string $defaultFolder): string
    {
        $cleanPath = str_replace('\\', '/', trim($fotoPath, '/'));
        if ($cleanPath === '') {
            return '';
        }

        if (Str::startsWith($cleanPath, ['http://', 'https://', 'data:'])) {
            return $cleanPath;
        }

        if (Str::startsWith($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        } elseif (Str::startsWith($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }

        [$folder, $subPath] = array_pad(explode('/', $cleanPath, 2), 2, '');
        if (in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== '') {
            $relative = $folder . '/' . $subPath;
            return Storage::disk('public')->exists($relative)
                ? asset('storage/' . $relative)
                : route('media.image', ['folder' => $folder, 'path' => $subPath]);
        }

        $relative = $defaultFolder . '/' . ltrim($cleanPath, '/');
        if (Storage::disk('public')->exists($relative)) {
            return asset('storage/' . $relative);
        }

        return '';
    }
}
