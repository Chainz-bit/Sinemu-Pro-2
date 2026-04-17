<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
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
    public function index(Request $request): View
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();
        $hasNamaColumn = Schema::hasColumn('users', 'nama');
        $hasNameColumn = Schema::hasColumn('users', 'name');
        $hasLostHomeFlag = Schema::hasColumn('laporan_barang_hilangs', 'tampil_di_home');
        $hasFoundHomeFlag = Schema::hasColumn('barangs', 'tampil_di_home');

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
            if (in_array($status, ['pending', 'disetujui', 'ditolak'], true)) {
                $query->where('klaims.status_klaim', $status);
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
                    fputcsv($handle, [
                        $claim->pelapor_nama,
                        $claim->barang_temuan,
                        $claim->barang_hilang,
                        $claim->lokasi,
                        $claim->status_klaim,
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
            $klaim->update([
                'status_klaim' => 'disetujui',
                'admin_id' => $adminId,
            ]);
            if ($klaim->barang) {
                $klaim->barang->update(['status_barang' => 'sudah_diklaim']);
            }

            if (!is_null($klaim->user_id)) {
                $namaBarang = $klaim->barang?->nama_barang ?? $klaim->laporanHilang?->nama_barang ?? 'barang Anda';
                UserNotificationService::notifyUser(
                    userId: (int) $klaim->user_id,
                    type: 'klaim_disetujui',
                    title: 'Klaim Disetujui',
                    message: 'Admin menyetujui klaim untuk '.$namaBarang.'.',
                    actionUrl: route('user.dashboard'),
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
            $klaim->update([
                'status_klaim' => 'ditolak',
                'admin_id' => $adminId,
            ]);
            if ($klaim->barang && $klaim->barang->status_barang === 'dalam_proses_klaim') {
                $klaim->barang->update(['status_barang' => 'tersedia']);
            }

            if (!is_null($klaim->user_id)) {
                $namaBarang = $klaim->barang?->nama_barang ?? $klaim->laporanHilang?->nama_barang ?? 'barang Anda';
                UserNotificationService::notifyUser(
                    userId: (int) $klaim->user_id,
                    type: 'klaim_ditolak',
                    title: 'Klaim Ditolak',
                    message: 'Admin menolak klaim untuk '.$namaBarang.'.',
                    actionUrl: route('user.dashboard'),
                    meta: ['klaim_id' => $klaim->id]
                );
            }
        }

        return redirect()->back()->with('status', 'Klaim berhasil ditolak.');
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

        $statusMap = [
            'pending' => ['Menunggu Verifikasi', 'status-dalam_peninjauan'],
            'disetujui' => ['Disetujui', 'status-selesai'],
            'ditolak' => ['Ditolak', 'status-ditolak'],
        ];
        [$statusLabel, $statusClass] = $statusMap[$klaim->status_klaim] ?? ['Status Tidak Diketahui', 'status-diproses'];

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
            'pelaporEmail'
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
}
