<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
use App\Support\ClaimStatusPresenter;
use App\Support\WorkflowStatus;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class ClaimVerificationController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();
        $hasNamaColumn = Schema::hasColumn('users', 'nama');
        $hasNameColumn = Schema::hasColumn('users', 'name');
        $hasLostHomeFlag = Schema::hasColumn('laporan_barang_hilangs', 'tampil_di_home');
        $hasFoundHomeFlag = Schema::hasColumn('barangs', 'tampil_di_home');
        $hasClaimVerificationStatus = Schema::hasColumn('klaims', 'status_verifikasi');

        $pelaporSelect = '"Pengguna"';
        if ($hasNamaColumn && $hasNameColumn) {
            $pelaporSelect = 'COALESCE(users.nama, users.name, "Pengguna")';
        } elseif ($hasNamaColumn) {
            $pelaporSelect = 'COALESCE(users.nama, "Pengguna")';
        } elseif ($hasNameColumn) {
            $pelaporSelect = 'COALESCE(users.name, "Pengguna")';
        }

        $query = Klaim::query()
            ->leftJoin('laporan_barang_hilangs', 'laporan_barang_hilangs.id', '=', 'klaims.laporan_hilang_id')
            ->leftJoin('barangs', 'barangs.id', '=', 'klaims.barang_id')
            ->leftJoin('users', 'users.id', '=', 'klaims.user_id')
            ->leftJoin('admins', 'admins.id', '=', 'klaims.admin_id')
            ->select([
                'klaims.id',
                'klaims.admin_id',
                'klaims.barang_id',
                'klaims.laporan_hilang_id',
                'klaims.status_klaim',
                DB::raw(($hasClaimVerificationStatus ? 'COALESCE(klaims.status_verifikasi, "")' : '""').' as status_verifikasi'),
                'klaims.catatan',
                'klaims.created_at',
                'klaims.updated_at',
                DB::raw($pelaporSelect.' as pelapor_nama'),
                DB::raw('COALESCE(laporan_barang_hilangs.nama_barang, "-") as barang_hilang'),
                DB::raw('COALESCE(barangs.nama_barang, "-") as barang_temuan'),
                DB::raw('COALESCE(barangs.foto_barang, laporan_barang_hilangs.foto_barang, "") as foto_barang'),
                DB::raw('COALESCE(barangs.lokasi_ditemukan, "-") as lokasi'),
                DB::raw('COALESCE(barangs.status_barang, "-") as status_barang_temuan'),
                DB::raw('COALESCE(admins.nama, "-") as admin_nama'),
                DB::raw(($hasLostHomeFlag ? 'COALESCE(laporan_barang_hilangs.tampil_di_home, 0)' : '0').' as laporan_hilang_tampil_di_home'),
                DB::raw(($hasFoundHomeFlag ? 'COALESCE(barangs.tampil_di_home, 0)' : '0').' as barang_tampil_di_home'),
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search, $hasNamaColumn, $hasNameColumn) {
                $q->where('laporan_barang_hilangs.nama_barang', 'like', '%'.$search.'%')
                    ->orWhere('barangs.nama_barang', 'like', '%'.$search.'%');

                if ($hasNamaColumn) {
                    $q->orWhere('users.nama', 'like', '%'.$search.'%');
                }

                if ($hasNameColumn) {
                    $q->orWhere('users.name', 'like', '%'.$search.'%');
                }
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->query('status');
            if (in_array($status, ['menunggu', 'pending', 'disetujui', 'ditolak', 'selesai'], true)) {
                if ($hasClaimVerificationStatus) {
                    if (in_array($status, ['menunggu', 'pending'], true)) {
                        $query->whereIn('klaims.status_verifikasi', [WorkflowStatus::CLAIM_SUBMITTED, WorkflowStatus::CLAIM_UNDER_REVIEW]);
                    } elseif ($status === 'disetujui') {
                        $query->where('klaims.status_verifikasi', WorkflowStatus::CLAIM_APPROVED);
                    } elseif ($status === 'ditolak') {
                        $query->where('klaims.status_verifikasi', WorkflowStatus::CLAIM_REJECTED);
                    } else {
                        $query->where('klaims.status_verifikasi', WorkflowStatus::CLAIM_COMPLETED);
                    }
                } else {
                    if (in_array($status, ['menunggu', 'pending'], true)) {
                        $query->where('klaims.status_klaim', 'pending');
                    } elseif ($status === 'selesai') {
                        $query->where('klaims.status_klaim', 'disetujui')
                            ->where('barangs.status_barang', 'sudah_dikembalikan');
                    } else {
                        $query->where('klaims.status_klaim', $status);
                    }
                }
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('klaims.created_at', $request->query('date'));
        }

        $sort = (string) $request->query('sort', 'terbaru');
        if ($sort === 'terlama') {
            $query->orderBy('klaims.updated_at');
        } else {
            $query->orderByDesc('klaims.updated_at');
        }

        if ($request->boolean('export')) {
            $exportClaims = $query->get();

            return new StreamedResponse(function () use ($exportClaims) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Pelapor', 'Barang Temuan', 'Barang Hilang', 'Lokasi', 'Status Klaim', 'Tanggal Klaim']);

                foreach ($exportClaims as $claim) {
                    $statusKey = ClaimStatusPresenter::key(
                        statusKlaim: (string) $claim->status_klaim,
                        statusVerifikasi: (string) ($claim->status_verifikasi ?? ''),
                        statusBarang: (string) ($claim->status_barang_temuan ?? '')
                    );
                    fputcsv($handle, [
                        $claim->pelapor_nama,
                        $claim->barang_temuan,
                        $claim->barang_hilang,
                        $claim->lokasi,
                        ClaimStatusPresenter::label($statusKey),
                        $claim->created_at,
                    ]);
                }

                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="verifikasi-klaim.csv"',
            ]);
        }

        $claims = $query->paginate(12)->withQueryString();

        return view('admin.pages.claim-verifications', compact('claims', 'admin', 'sort'));
    }

    public function approve(Request $request, Klaim $klaim): RedirectResponse
    {
        $this->ensureClaimOwnedByAdmin($klaim);
        $adminId = (int) Auth::guard('admin')->id();

        if ($klaim->status_klaim === 'pending') {
            $validated = $request->validate($this->verificationRules());
            $verification = $this->buildVerificationResult($validated);
            if (!$verification['can_approve']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Klaim tidak dapat disetujui. Skor verifikasi minimal 75 dan semua poin kritikal harus lolos.');
            }

            $payload = [
                'status_klaim' => 'disetujui',
                'admin_id' => $adminId,
            ];
            if (Schema::hasColumn('klaims', 'status_verifikasi')) {
                $payload['status_verifikasi'] = WorkflowStatus::CLAIM_APPROVED;
            }
            if (Schema::hasColumn('klaims', 'hasil_checklist')) {
                $payload['hasil_checklist'] = $verification['checklist'];
            }
            if (Schema::hasColumn('klaims', 'skor_validitas')) {
                $payload['skor_validitas'] = $verification['score'];
            }
            if (Schema::hasColumn('klaims', 'catatan_verifikasi_admin')) {
                $payload['catatan_verifikasi_admin'] = $validated['catatan_verifikasi_admin'] ?? null;
            }
            if (Schema::hasColumn('klaims', 'alasan_penolakan')) {
                $payload['alasan_penolakan'] = null;
            }
            if (Schema::hasColumn('klaims', 'diverifikasi_at')) {
                $payload['diverifikasi_at'] = now();
            }
            $klaim->update($payload);
            if ($klaim->barang) {
                $klaim->barang->update(['status_barang' => 'sudah_diklaim']);
            }
            if ($klaim->pencocokan) {
                $klaim->pencocokan->update(['status_pencocokan' => WorkflowStatus::MATCH_CLAIM_APPROVED]);
            }

            if (!is_null($klaim->user_id)) {
                $namaBarang = $klaim->barang?->nama_barang ?? $klaim->laporanHilang?->nama_barang ?? 'barang Anda';
                UserNotificationService::notifyUser(
                    userId: (int) $klaim->user_id,
                    type: 'klaim_disetujui',
                    title: 'Klaim Disetujui',
                    message: 'Admin menyetujui klaim untuk '.$namaBarang.'.',
                    actionUrl: route('user.claim-history'),
                    meta: ['klaim_id' => $klaim->id]
                );
            }
        }

        return redirect()->back()->with('status', 'Klaim berhasil disetujui.');
    }

    public function reject(Request $request, Klaim $klaim): RedirectResponse
    {
        $this->ensureClaimOwnedByAdmin($klaim);
        $adminId = (int) Auth::guard('admin')->id();

        if ($klaim->status_klaim === 'pending') {
            $validated = $request->validate($this->verificationRules(true));
            $verification = $this->buildVerificationResult($validated);

            $payload = [
                'status_klaim' => 'ditolak',
                'admin_id' => $adminId,
            ];
            if (Schema::hasColumn('klaims', 'status_verifikasi')) {
                $payload['status_verifikasi'] = WorkflowStatus::CLAIM_REJECTED;
            }
            if (Schema::hasColumn('klaims', 'hasil_checklist')) {
                $payload['hasil_checklist'] = $verification['checklist'];
            }
            if (Schema::hasColumn('klaims', 'skor_validitas')) {
                $payload['skor_validitas'] = $verification['score'];
            }
            if (Schema::hasColumn('klaims', 'catatan_verifikasi_admin')) {
                $payload['catatan_verifikasi_admin'] = $validated['catatan_verifikasi_admin'] ?? null;
            }
            if (Schema::hasColumn('klaims', 'alasan_penolakan')) {
                $payload['alasan_penolakan'] = $validated['alasan_penolakan'];
            }
            if (Schema::hasColumn('klaims', 'diverifikasi_at')) {
                $payload['diverifikasi_at'] = now();
            }
            $klaim->update($payload);
            if ($klaim->barang && $klaim->barang->status_barang === 'dalam_proses_klaim') {
                $klaim->barang->update(['status_barang' => 'tersedia']);
            }
            if ($klaim->pencocokan) {
                $klaim->pencocokan->update(['status_pencocokan' => WorkflowStatus::MATCH_CLAIM_REJECTED]);
            }

            if (!is_null($klaim->user_id)) {
                $namaBarang = $klaim->barang?->nama_barang ?? $klaim->laporanHilang?->nama_barang ?? 'barang Anda';
                UserNotificationService::notifyUser(
                    userId: (int) $klaim->user_id,
                    type: 'klaim_ditolak',
                    title: 'Klaim Ditolak',
                    message: 'Admin menolak klaim untuk '.$namaBarang.'.',
                    actionUrl: route('user.claim-history'),
                    meta: ['klaim_id' => $klaim->id]
                );
            }
        }

        return redirect()->back()->with('status', 'Klaim berhasil ditolak.');
    }

    public function complete(Klaim $klaim): RedirectResponse
    {
        $this->ensureClaimOwnedByAdmin($klaim);
        abort_if($klaim->status_klaim !== 'disetujui', 422, 'Klaim harus disetujui sebelum ditandai selesai.');

        $payload = [
            'admin_id' => (int) Auth::guard('admin')->id(),
        ];
        if (Schema::hasColumn('klaims', 'status_verifikasi')) {
            $payload['status_verifikasi'] = WorkflowStatus::CLAIM_COMPLETED;
        }
        $klaim->update($payload);

        if ($klaim->barang) {
            $barangPayload = [
                'status_barang' => 'sudah_dikembalikan',
            ];
            if (Schema::hasColumn('barangs', 'status_laporan')) {
                $barangPayload['status_laporan'] = WorkflowStatus::REPORT_COMPLETED;
            }
            if (Schema::hasColumn('barangs', 'tampil_di_home')) {
                $barangPayload['tampil_di_home'] = false;
            }
            $klaim->barang->update($barangPayload);
        }

        if ($klaim->laporanHilang) {
            $lostPayload = [];
            if (Schema::hasColumn('laporan_barang_hilangs', 'status_laporan')) {
                $lostPayload['status_laporan'] = WorkflowStatus::REPORT_COMPLETED;
            }
            if (Schema::hasColumn('laporan_barang_hilangs', 'tampil_di_home')) {
                $lostPayload['tampil_di_home'] = false;
            }
            if ($lostPayload !== []) {
                $klaim->laporanHilang->update($lostPayload);
            }
        }

        if ($klaim->pencocokan) {
            $klaim->pencocokan->update(['status_pencocokan' => WorkflowStatus::MATCH_COMPLETED]);
        }

        if (!is_null($klaim->user_id)) {
            $namaBarang = $klaim->barang?->nama_barang ?? $klaim->laporanHilang?->nama_barang ?? 'barang Anda';
            UserNotificationService::notifyUser(
                userId: (int) $klaim->user_id,
                type: 'klaim_selesai',
                title: 'Barang Sudah Diserahkan',
                message: 'Proses klaim '.$namaBarang.' telah selesai dan barang dinyatakan dikembalikan.',
                actionUrl: route('user.claim-history'),
                meta: ['klaim_id' => $klaim->id]
            );
        }

        return redirect()->back()->with('status', 'Klaim ditandai selesai.');
    }

    public function show(Klaim $klaim): View
    {
        $this->ensureClaimOwnedByAdmin($klaim);
        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        $klaim->load([
            'barang.kategori:id,nama_kategori',
            'laporanHilang:id,nama_barang,lokasi_hilang,tanggal_hilang,keterangan,foto_barang,ciri_khusus,bukti_kepemilikan',
            'user:id,name,nama,email',
            'admin:id,nama,email',
        ]);

        $statusKey = ClaimStatusPresenter::key(
            statusKlaim: (string) $klaim->status_klaim,
            statusVerifikasi: Schema::hasColumn('klaims', 'status_verifikasi') ? (string) ($klaim->status_verifikasi ?? '') : null,
            statusBarang: (string) ($klaim->barang?->status_barang ?? '')
        );
        $statusLabel = match ($statusKey) {
            'menunggu' => 'Menunggu Verifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => 'Selesai',
        };
        $statusClass = ClaimStatusPresenter::cssClass($statusKey);

        $barang = $klaim->barang;
        $laporanHilang = $klaim->laporanHilang;
        $namaBarang = $barang?->nama_barang ?? $laporanHilang?->nama_barang ?? 'Barang tidak ditemukan';
        $kategoriNama = $barang?->kategori?->nama_kategori ?? 'Tidak tersedia';
        $lokasi = $barang?->lokasi_ditemukan ?? $laporanHilang?->lokasi_hilang ?? '-';
        $tanggalLaporan = $barang?->tanggal_ditemukan ?? $laporanHilang?->tanggal_hilang ?? $klaim->created_at;
        $deskripsi = $barang?->deskripsi ?? $laporanHilang?->keterangan ?? 'Belum ada deskripsi.';
        $fotoUrl = $this->resolveItemImageUrl((string) ($barang?->foto_barang ?? $laporanHilang?->foto_barang ?? ''));
        $statusBarangMap = [
            'tersedia' => ['Tersedia', 'status-dalam_peninjauan'],
            'dalam_proses_klaim' => ['Dalam Proses Klaim', 'status-diproses'],
            'sudah_diklaim' => ['Sudah Diklaim', 'status-selesai'],
            'sudah_dikembalikan' => ['Selesai', 'status-selesai'],
        ];
        [$statusBarangLabel, $statusBarangClass] = $statusBarangMap[(string) ($barang?->status_barang ?? '')] ?? ['Tidak tersedia', 'status-dalam_peninjauan'];

        $pelapor = $klaim->user;
        $pelaporNama = $pelapor?->nama ?? $pelapor?->name ?? 'Pengguna';
        $pelaporEmail = $pelapor?->email ?? '-';

        return view('admin.pages.claim-verification-detail', compact(
            'admin',
            'klaim',
            'statusLabel',
            'statusClass',
            'namaBarang',
            'kategoriNama',
            'lokasi',
            'tanggalLaporan',
            'deskripsi',
            'fotoUrl',
            'statusBarangLabel',
            'statusBarangClass',
            'pelaporNama',
            'pelaporEmail',
            'statusKey'
        ));
    }

    public function destroy(Klaim $klaim): RedirectResponse
    {
        abort_if(!Auth::guard('admin')->check(), 403);

        foreach ((array) ($klaim->bukti_foto ?? []) as $path) {
            if (is_string($path) && trim($path) !== '') {
                Storage::disk('public')->delete($path);
            }
        }

        $klaim->delete();

        return redirect()->back()->with('status', 'Data klaim berhasil dihapus.');
    }

    private function ensureClaimOwnedByAdmin(Klaim $klaim): void
    {
        $adminId = Auth::guard('admin')->id();
        if (is_null($klaim->admin_id)) {
            return;
        }

        abort_if((int) $klaim->admin_id !== (int) $adminId, 403);
    }

    private function resolveItemImageUrl(string $fotoPath): string
    {
        $cleanPath = str_replace('\\', '/', trim($fotoPath, '/'));
        if ($cleanPath === '') {
            return asset('img/login-image.png');
        }

        if (Str::startsWith($cleanPath, ['http://', 'https://'])) {
            return $cleanPath;
        }

        if (Str::startsWith($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        } elseif (Str::startsWith($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }

        [$folder, $subPath] = array_pad(explode('/', $cleanPath, 2), 2, '');
        if (in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== '') {
            return route('media.image', ['folder' => $folder, 'path' => $subPath]);
        }

        return asset('storage/' . $cleanPath);
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function verificationRules(bool $withRejectionReason = false): array
    {
        return [
            'identitas_pelapor_valid' => ['required', 'in:0,1'],
            'detail_barang_valid' => ['required', 'in:0,1'],
            'kronologi_valid' => ['required', 'in:0,1'],
            'bukti_visual_valid' => ['required', 'in:0,1'],
            'kecocokan_data_laporan' => ['required', 'in:0,1'],
            'catatan_verifikasi_admin' => ['nullable', 'string', 'max:2000'],
            'alasan_penolakan' => $withRejectionReason
                ? ['required', 'string', 'max:2000']
                : ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @param array<string,mixed> $validated
     * @return array{score:int,checklist:array<string,bool>,can_approve:bool}
     */
    private function buildVerificationResult(array $validated): array
    {
        $checklist = [
            'identitas_pelapor_valid' => ((string) ($validated['identitas_pelapor_valid'] ?? '0')) === '1',
            'detail_barang_valid' => ((string) ($validated['detail_barang_valid'] ?? '0')) === '1',
            'kronologi_valid' => ((string) ($validated['kronologi_valid'] ?? '0')) === '1',
            'bukti_visual_valid' => ((string) ($validated['bukti_visual_valid'] ?? '0')) === '1',
            'kecocokan_data_laporan' => ((string) ($validated['kecocokan_data_laporan'] ?? '0')) === '1',
        ];

        $weights = [
            'identitas_pelapor_valid' => 20,
            'detail_barang_valid' => 25,
            'kronologi_valid' => 20,
            'bukti_visual_valid' => 20,
            'kecocokan_data_laporan' => 15,
        ];

        $score = 0;
        foreach ($weights as $key => $weight) {
            if (($checklist[$key] ?? false) === true) {
                $score += $weight;
            }
        }

        $criticalChecksPassed =
            ($checklist['detail_barang_valid'] ?? false)
            && ($checklist['kronologi_valid'] ?? false)
            && ($checklist['bukti_visual_valid'] ?? false);

        return [
            'score' => $score,
            'checklist' => $checklist,
            'can_approve' => $criticalChecksPassed && $score >= 75,
        ];
    }
}
