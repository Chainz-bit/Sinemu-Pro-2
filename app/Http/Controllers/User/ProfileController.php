<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Support\ClaimStatusPresenter;
use App\Support\ReportStatusPresenter;
use App\Support\WorkflowStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_if(!$user, 403);

        $laporanDiajukanQuery = LaporanBarangHilang::query()->where('user_id', (int) $user->id);
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            $laporanDiajukanQuery->where('sumber_laporan', 'lapor_hilang');
        }

        $laporanDiajukan = (clone $laporanDiajukanQuery)->count();
        $hasClaimVerification = Schema::hasColumn('klaims', 'status_verifikasi');

        $klaimMenungguQuery = Klaim::query()->where('user_id', (int) $user->id);
        if ($hasClaimVerification) {
            $klaimMenungguQuery->whereIn('status_verifikasi', [WorkflowStatus::CLAIM_SUBMITTED, WorkflowStatus::CLAIM_UNDER_REVIEW]);
        } else {
            $klaimMenungguQuery->where('status_klaim', 'pending');
        }
        $klaimMenunggu = $klaimMenungguQuery->count();

        $klaimSelesaiQuery = Klaim::query()->where('user_id', (int) $user->id);
        if ($hasClaimVerification) {
            $klaimSelesaiQuery->whereIn('status_verifikasi', [
                WorkflowStatus::CLAIM_APPROVED,
                WorkflowStatus::CLAIM_REJECTED,
                WorkflowStatus::CLAIM_COMPLETED,
            ]);
        } else {
            $klaimSelesaiQuery->whereIn('status_klaim', ['disetujui', 'ditolak']);
        }
        $klaimSelesai = $klaimSelesaiQuery->count();

        $recentActivities = $this->buildRecentActivities((int) $user->id);
        $profileAvatar = $this->resolveAvatarUrl($user);
        [$verificationLabel, $verificationClass] = $this->resolveVerificationStatus($user);

        return view('user.pages.profile', compact(
            'user',
            'laporanDiajukan',
            'klaimMenunggu',
            'klaimSelesai',
            'recentActivities',
            'profileAvatar',
            'verificationLabel',
            'verificationClass'
        ));
    }

    private function buildRecentActivities(int $userId): Collection
    {
        $lostReportActivities = LaporanBarangHilang::query()
            ->where('user_id', $userId)
            ->when(Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan'), function ($query) {
                $query->where('sumber_laporan', 'lapor_hilang');
            })
            ->select(array_values(array_filter([
                'id',
                'nama_barang',
                Schema::hasColumn('laporan_barang_hilangs', 'status_laporan') ? 'status_laporan' : null,
                'created_at',
                'updated_at',
            ])))
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (LaporanBarangHilang $report) {
                $reportStatus = ReportStatusPresenter::key($report->status_laporan ?? null);
                [$statusKey, $statusClass, $statusLabel] = match ($reportStatus) {
                    WorkflowStatus::REPORT_APPROVED => ['terverifikasi', 'dalam_peninjauan', 'Terverifikasi'],
                    WorkflowStatus::REPORT_REJECTED => ['tidak_disetujui', 'ditolak', 'Tidak Disetujui'],
                    WorkflowStatus::REPORT_MATCHED, WorkflowStatus::REPORT_CLAIMED => ['sedang_diproses', 'diproses', 'Sedang Diproses'],
                    WorkflowStatus::REPORT_COMPLETED => ['selesai', 'selesai', 'Selesai'],
                    default => ['menunggu_tinjauan', 'dalam_peninjauan', 'Menunggu Tinjauan'],
                };

                return (object) [
                    'activity_at' => strtotime((string) ($report->updated_at ?? $report->created_at)),
                    'title' => 'Anda mengirim laporan barang hilang ' . $report->nama_barang,
                    'timestamp' => $report->updated_at ?? $report->created_at,
                    'status_class' => $statusClass,
                    'status_label' => $statusLabel,
                    'detail_url' => $this->resolveLostReportDetailUrl((int) $report->id, $statusKey),
                ];
            });

        $claimActivities = Klaim::query()
            ->where('user_id', $userId)
            ->with(['barang:id,nama_barang', 'laporanHilang:id,nama_barang'])
            ->latest('updated_at')
            ->limit(8)
            ->get(array_values(array_filter([
                'id',
                'barang_id',
                'laporan_hilang_id',
                'status_klaim',
                Schema::hasColumn('klaims', 'status_verifikasi') ? 'status_verifikasi' : null,
                'created_at',
                'updated_at',
            ])))
            ->map(function (Klaim $claim) {
                $namaBarang = $claim->barang?->nama_barang
                    ?? $claim->laporanHilang?->nama_barang
                    ?? 'barang';

                $claimKey = ClaimStatusPresenter::key(
                    statusKlaim: (string) $claim->status_klaim,
                    statusVerifikasi: (string) ($claim->status_verifikasi ?? ''),
                    statusBarang: null
                );
                [$statusClass, $statusLabel] = match ($claimKey) {
                    'ditolak' => ['ditolak', 'Tidak Disetujui'],
                    'disetujui' => ['diproses', 'Sedang Diproses'],
                    'selesai' => ['selesai', 'Selesai'],
                    default => ['dalam_peninjauan', 'Menunggu Tinjauan'],
                };
                $kataKerja = match ($claimKey) {
                    'disetujui' => 'sedang diproses',
                    'ditolak' => 'tidak disetujui',
                    'selesai' => 'selesai',
                    default => 'menunggu tinjauan',
                };

                return (object) [
                    'activity_at' => strtotime((string) ($claim->updated_at ?? $claim->created_at)),
                    'title' => 'Klaim barang ' . $namaBarang . ' ' . $kataKerja,
                    'timestamp' => $claim->updated_at ?? $claim->created_at,
                    'status_class' => $statusClass,
                    'status_label' => $statusLabel,
                    'detail_url' => $this->resolveClaimDetailUrl($claim),
                ];
            });

        return $lostReportActivities
            ->merge($claimActivities)
            ->sortByDesc('activity_at')
            ->take(8)
            ->values();
    }

    private function resolveAvatarUrl(User $user): string
    {
        $defaultAvatar = asset('img/profil.jpg');
        $profilePath = trim((string) ($user->profil ?? ''));
        if ($profilePath === '') {
            return $defaultAvatar;
        }

        if (str_starts_with($profilePath, 'http://') || str_starts_with($profilePath, 'https://')) {
            return $profilePath;
        }

        $normalized = str_replace('\\', '/', ltrim($profilePath, '/'));
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        } elseif (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }

        [$folder, $subPath] = array_pad(explode('/', $normalized, 2), 2, '');
        if (in_array($folder, ['profil-admin', 'profil-user', 'barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== '') {
            if (Storage::disk('public')->exists($normalized)) {
                $absolutePath = Storage::disk('public')->path($normalized);
                $mimeType = Storage::disk('public')->mimeType($normalized) ?: 'image/jpeg';
                $binary = @file_get_contents($absolutePath);
                if ($binary !== false) {
                    return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
                }

                return route('media.image', ['folder' => $folder, 'path' => $subPath]);
            }

            return $defaultAvatar;
        }

        if (Storage::disk('public')->exists($normalized)) {
            $absolutePath = Storage::disk('public')->path($normalized);
            $mimeType = Storage::disk('public')->mimeType($normalized) ?: 'image/jpeg';
            $binary = @file_get_contents($absolutePath);
            if ($binary !== false) {
                return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
            }

            return asset('storage/' . $normalized);
        }

        return $defaultAvatar;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveVerificationStatus(User $user): array
    {
        if (!is_null($user->email_verified_at)) {
            return ['Terverifikasi', 'is-active'];
        }

        return ['Belum Verifikasi', 'is-pending'];
    }

    private function resolveLostReportDetailUrl(int $reportId, string $status): string
    {
        if (in_array($status, ['menunggu_tinjauan', 'tidak_disetujui'], true)) {
            return route('user.lost-reports.create', ['edit' => $reportId]);
        }

        return route('home.lost-detail', $reportId);
    }

    private function resolveClaimDetailUrl(Klaim $claim): string
    {
        if (!empty($claim->barang_id)) {
            return route('home.found-detail', $claim->barang_id);
        }

        if (!empty($claim->laporan_hilang_id)) {
            return route('home.lost-detail', $claim->laporan_hilang_id);
        }

        return route('home');
    }
}
