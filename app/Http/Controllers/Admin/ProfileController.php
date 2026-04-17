<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Barang;
use App\Models\Klaim;
use App\Support\ClaimStatusPresenter;
use App\Support\WorkflowStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();
        abort_if(!$admin, 403);

        $laporanDiajukan = Barang::query()
            ->where('admin_id', $admin->id)
            ->count();

        $hasClaimVerification = Schema::hasColumn('klaims', 'status_verifikasi');

        $klaimMenungguQuery = Klaim::query()->where('admin_id', $admin->id);
        if ($hasClaimVerification) {
            $klaimMenungguQuery->whereIn('status_verifikasi', [WorkflowStatus::CLAIM_SUBMITTED, WorkflowStatus::CLAIM_UNDER_REVIEW]);
        } else {
            $klaimMenungguQuery->where('status_klaim', 'pending');
        }
        $klaimMenunggu = $klaimMenungguQuery->count();

        $selesaiDitanganiQuery = Klaim::query()->where('admin_id', $admin->id);
        if ($hasClaimVerification) {
            $selesaiDitanganiQuery->whereIn('status_verifikasi', [
                WorkflowStatus::CLAIM_APPROVED,
                WorkflowStatus::CLAIM_REJECTED,
                WorkflowStatus::CLAIM_COMPLETED,
            ]);
        } else {
            $selesaiDitanganiQuery->whereIn('status_klaim', ['disetujui', 'ditolak']);
        }
        $selesaiDitangani = $selesaiDitanganiQuery->count();

        $recentActivities = $this->buildRecentActivities((int) $admin->id);
        $profileAvatar = $this->resolveAvatarUrl($admin);
        [$verificationLabel, $verificationClass] = $this->resolveVerificationStatus($admin);

        return view('admin.pages.profile', compact(
            'admin',
            'laporanDiajukan',
            'klaimMenunggu',
            'selesaiDitangani',
            'recentActivities',
            'profileAvatar',
            'verificationLabel',
            'verificationClass'
        ));
    }

    public function edit(): View
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();
        abort_if(!$admin, 403);

        $profileAvatar = $this->resolveAvatarUrl($admin);
        [$verificationLabel, $verificationClass] = $this->resolveVerificationStatus($admin);
        $kecamatanOptions = $this->getKecamatanOptions();

        return view('admin.pages.profile-edit', compact(
            'admin',
            'profileAvatar',
            'verificationLabel',
            'verificationClass',
            'kecamatanOptions'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();
        abort_if(!$admin, 403);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
            'instansi' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required', 'string', 'max:1200'],
            'profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $photo = $request->file('profil');
        if ($photo) {
            $oldProfilePath = trim((string) ($admin->profil ?? ''));
            if ($oldProfilePath !== '' && !str_starts_with($oldProfilePath, 'http://') && !str_starts_with($oldProfilePath, 'https://') && !str_starts_with($oldProfilePath, '/')) {
                Storage::disk('public')->delete($oldProfilePath);
            }

            $validated['profil'] = $photo->store('profil-admin/' . now()->format('Y/m'), 'public');
        }

        $admin->forceFill($validated)->save();

        return redirect()
            ->route('admin.profile')
            ->with('status', 'Profil admin berhasil diperbarui.');
    }

    private function buildRecentActivities(int $adminId): Collection
    {
        $hasStatusVerifikasi = Schema::hasColumn('klaims', 'status_verifikasi');

        $inputActivities = Barang::query()
            ->where('admin_id', $adminId)
            ->latest('updated_at')
            ->limit(8)
            ->get([
                'id',
                'nama_barang',
                'status_barang',
                'created_at',
                'updated_at',
            ])
            ->map(function (Barang $barang) {
                $statusClass = match ($barang->status_barang) {
                    'sudah_diklaim', 'sudah_dikembalikan' => 'selesai',
                    'dalam_proses_klaim' => 'dalam_peninjauan',
                    default => 'diproses',
                };

                $statusLabel = match ($barang->status_barang) {
                    'sudah_diklaim', 'sudah_dikembalikan' => 'SELESAI',
                    'dalam_proses_klaim' => 'SEDANG DIKLAIM',
                    default => 'DIPROSES',
                };

                return (object) [
                    'activity_at' => strtotime((string) ($barang->updated_at ?? $barang->created_at)),
                    'title' => 'Anda memasukkan data barang temuan ' . $barang->nama_barang,
                    'timestamp' => $barang->updated_at ?? $barang->created_at,
                    'status_class' => $statusClass,
                    'status_label' => $statusLabel,
                    'detail_url' => route('admin.found-items.show', $barang->id),
                ];
            });

        $claimActivities = Klaim::query()
            ->where('admin_id', $adminId)
            ->with([
                'barang:id,nama_barang',
                'laporanHilang:id,nama_barang',
            ])
            ->latest('updated_at')
            ->limit(8)
            ->get(array_values(array_filter([
                'id',
                'barang_id',
                'laporan_hilang_id',
                'status_klaim',
                $hasStatusVerifikasi ? 'status_verifikasi' : null,
                'created_at',
                'updated_at',
            ])))
            ->map(function (Klaim $klaim) {
                $namaBarang = $klaim->barang?->nama_barang
                    ?? $klaim->laporanHilang?->nama_barang
                    ?? 'barang';

                $claimKey = ClaimStatusPresenter::key(
                    statusKlaim: (string) $klaim->status_klaim,
                    statusVerifikasi: (string) ($klaim->status_verifikasi ?? ''),
                    statusBarang: null
                );
                $statusClass = match ($claimKey) {
                    'selesai' => 'selesai',
                    'ditolak' => 'ditolak',
                    'disetujui' => 'diproses',
                    default => 'dalam_peninjauan',
                };
                $statusLabel = ClaimStatusPresenter::label($claimKey);
                $kataKerja = match ($claimKey) {
                    'disetujui' => 'disetujui',
                    'ditolak' => 'ditolak',
                    'selesai' => 'diselesaikan',
                    default => 'menunggu verifikasi',
                };

                return (object) [
                    'activity_at' => strtotime((string) ($klaim->updated_at ?? $klaim->created_at)),
                    'title' => 'Klaim barang ' . $namaBarang . ' ' . $kataKerja,
                    'timestamp' => $klaim->updated_at ?? $klaim->created_at,
                    'status_class' => $statusClass,
                    'status_label' => $statusLabel,
                    'detail_url' => route('admin.claim-verifications.show', $klaim->id),
                ];
            });

        return $inputActivities
            ->merge($claimActivities)
            ->sortByDesc('activity_at')
            ->take(8)
            ->values();
    }

    private function resolveAvatarUrl(Admin $admin): string
    {
        $defaultAvatar = asset('img/profil.jpg');
        $profilePath = trim((string) ($admin->profil ?? ''));

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
                $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
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
            $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
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
    private function resolveVerificationStatus(Admin $admin): array
    {
        return match ((string) ($admin->status_verifikasi ?? 'active')) {
            'active' => ['Terverifikasi', 'is-active'],
            'pending' => ['Menunggu Verifikasi', 'is-pending'],
            'rejected' => ['Verifikasi Ditolak', 'is-rejected'],
            default => ['Status Tidak Diketahui', 'is-unknown'],
        };
    }

    /**
     * @return array<int, string>
     */
    private function getKecamatanOptions(): array
    {
        return [
            'Balongan',
            'Bongas',
            'Cantigi',
            'Cikedung',
            'Gabuswetan',
            'Gantar',
            'Haurgeulis',
            'Indramayu Kota',
            'Jatibarang',
            'Juntinyuat',
            'Kandanghaur',
            'Karangampel',
            'Kedokanbunder',
            'Kertasemaya',
            'Krangkeng',
            'Kroya',
            'Lelea',
            'Lobener',
            'Losarang',
            'Pasekan',
            'Patrol',
            'Sindang',
            'Sliyeg',
            'Sukagumiwang',
            'Sukra',
            'Terisi',
            'Tukdana',
            'Widasari',
        ];
    }
}
