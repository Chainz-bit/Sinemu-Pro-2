<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RiwayatKlaimController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'semua'));
        $type = trim((string) $request->query('type', 'semua'));

        $claimsQuery = Klaim::query()
            ->where('user_id', (int) $user->id)
            ->with([
                'barang:id,nama_barang,lokasi_ditemukan,foto_barang,status_barang,lokasi_pengambilan,alamat_pengambilan',
                'laporanHilang:id,nama_barang,lokasi_hilang,foto_barang',
                'admin:id,instansi,kecamatan,alamat_lengkap',
            ])
            ->latest('updated_at');

        if ($search !== '') {
            $claimsQuery->where(function ($query) use ($search) {
                $query->whereHas('barang', function ($barangQuery) use ($search) {
                    $barangQuery
                        ->where('nama_barang', 'like', '%' . $search . '%')
                        ->orWhere('lokasi_ditemukan', 'like', '%' . $search . '%');
                })->orWhereHas('laporanHilang', function ($lostQuery) use ($search) {
                    $lostQuery
                        ->where('nama_barang', 'like', '%' . $search . '%')
                        ->orWhere('lokasi_hilang', 'like', '%' . $search . '%');
                });
            });
        }

        if ($status === 'menunggu') {
            $claimsQuery->where('status_klaim', 'pending');
        } elseif ($status === 'ditolak') {
            $claimsQuery->where('status_klaim', 'ditolak');
        } elseif ($status === 'disetujui') {
            $claimsQuery->where('status_klaim', 'disetujui')->where(function ($query) {
                $query->whereDoesntHave('barang')
                    ->orWhereHas('barang', function ($barangQuery) {
                        $barangQuery->where('status_barang', '!=', 'sudah_dikembalikan');
                    });
            });
        } elseif ($status === 'selesai') {
            $claimsQuery->where('status_klaim', 'disetujui')->whereHas('barang', function ($barangQuery) {
                $barangQuery->where('status_barang', 'sudah_dikembalikan');
            });
        }

        if ($type === 'temuan') {
            $claimsQuery->whereNotNull('barang_id');
        } elseif ($type === 'hilang') {
            $claimsQuery->whereNotNull('laporan_hilang_id');
        }

        $claims = $claimsQuery->paginate(8)->withQueryString();

        $claims->setCollection(
            $claims->getCollection()->map(function (Klaim $claim) {
                [$statusText, $statusClass, $statusKey] = $this->resolveStatus($claim);

                $itemName = (string) ($claim->barang?->nama_barang ?? $claim->laporanHilang?->nama_barang ?? 'Klaim Barang');
                $itemType = !is_null($claim->barang_id) ? 'Barang Temuan' : 'Laporan Hilang';
                $locationPickup = $this->resolvePickupLocation($claim, $statusKey);
                $itemImage = $this->resolveItemImageUrl(
                    (string) ($claim->barang?->foto_barang ?? $claim->laporanHilang?->foto_barang ?? ''),
                    !is_null($claim->barang_id) ? 'barang-temuan' : 'barang-hilang'
                );

                return (object) [
                    'id' => (int) $claim->id,
                    'item_name' => $itemName,
                    'item_type' => $itemType,
                    'item_image' => $itemImage,
                    'submitted_at' => $claim->created_at,
                    'status_text' => $statusText,
                    'status_class' => $statusClass,
                    'status_key' => $statusKey,
                    'pickup_location' => $locationPickup,
                    'detail_url' => $this->resolveDetailUrl($claim),
                ];
            })
        );

        return view('user.pages.claim-history', [
            'user' => $user,
            'search' => $search,
            'statusFilter' => $status,
            'typeFilter' => $type,
            'claims' => $claims,
        ]);
    }

    public function destroy(Klaim $klaim)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless((int) $klaim->user_id === (int) $user->id, 403);

        foreach ((array) ($klaim->bukti_foto ?? []) as $path) {
            if (is_string($path) && trim($path) !== '') {
                Storage::disk('public')->delete($path);
            }
        }

        $klaim->delete();

        return redirect()
            ->route('user.claim-history', request()->query())
            ->with('status', 'Riwayat klaim berhasil dihapus.');
    }

    private function resolveStatus(Klaim $claim): array
    {
        if ((string) $claim->status_klaim === 'pending') {
            return ['MENUNGGU VERIFIKASI', 'status-dalam_peninjauan', 'menunggu'];
        }

        if ((string) $claim->status_klaim === 'ditolak') {
            return ['DITOLAK', 'status-ditolak', 'ditolak'];
        }

        if ((string) ($claim->barang?->status_barang ?? '') === 'sudah_dikembalikan') {
            return ['SELESAI', 'status-selesai', 'selesai'];
        }

        return ['DISETUJUI / BISA DIAMBIL', 'status-selesai', 'disetujui'];
    }

    private function resolvePickupLocation(Klaim $claim, string $statusKey): string
    {
        if ($statusKey === 'menunggu') {
            return 'Menunggu Persetujuan';
        }

        if ($statusKey === 'ditolak') {
            return '-';
        }

        if ($statusKey === 'selesai') {
            return 'Sudah Diambil';
        }

        $location = trim((string) ($claim->barang?->lokasi_pengambilan ?? ''));
        if ($location !== '') {
            return $location;
        }

        $address = trim((string) ($claim->barang?->alamat_pengambilan ?? ''));
        if ($address !== '') {
            return $address;
        }

        $kecamatan = trim((string) ($claim->admin?->kecamatan ?? ''));
        if ($kecamatan !== '') {
            return $kecamatan;
        }

        return trim((string) ($claim->admin?->instansi ?? 'Hubungi Admin'));
    }

    private function resolveDetailUrl(Klaim $claim): string
    {
        if (!is_null($claim->barang_id)) {
            return route('home.found-detail', $claim->barang_id);
        }

        if (!is_null($claim->laporan_hilang_id)) {
            return route('home.lost-detail', $claim->laporan_hilang_id);
        }

        return route('home') . '#hilang-temuan';
    }

    private function resolveItemImageUrl(string $fotoPath, string $defaultFolder): string
    {
        $cleanPath = trim($fotoPath, '/');
        if ($cleanPath === '') {
            return asset('img/login-image.png');
        }

        [$folder, $subPath] = array_pad(explode('/', $cleanPath, 2), 2, '');
        if (in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== '') {
            return route('media.image', ['folder' => $folder, 'path' => $subPath], false);
        }

        if ($subPath !== '') {
            return route('media.image', ['folder' => $defaultFolder, 'path' => $cleanPath], false);
        }

        return asset('storage/' . $cleanPath);
    }
}
