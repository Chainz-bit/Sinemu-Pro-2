<?php

namespace App\Services\User\Claims;

use App\Models\Klaim;
use App\Support\ClaimStatusPresenter;
use App\Support\WorkflowStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClaimHistoryService
{
    /**
     * @return array{claims:LengthAwarePaginator,search:string,statusFilter:string,typeFilter:string}
     */
    public function buildHistoryData(int $userId, array $query): array
    {
        $search = trim((string) ($query['search'] ?? ''));
        $status = trim((string) ($query['status'] ?? 'semua'));
        $type = trim((string) ($query['type'] ?? 'semua'));
        $hasStatusVerifikasi = Schema::hasColumn('klaims', 'status_verifikasi');

        $claimsQuery = Klaim::query()
            ->where('user_id', $userId)
            ->with([
                'barang:id,nama_barang,lokasi_ditemukan,foto_barang,status_barang,lokasi_pengambilan,alamat_pengambilan,penanggung_jawab_pengambilan,kontak_pengambilan,jam_layanan_pengambilan,catatan_pengambilan',
                'laporanHilang:id,nama_barang,lokasi_hilang,foto_barang',
                'admin:id,instansi,kecamatan,alamat_lengkap',
            ])
            ->latest('updated_at');

        if ($search !== '') {
            $claimsQuery->where(function ($builder) use ($search) {
                $builder->whereHas('barang', function ($barangQuery) use ($search) {
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

        if ($status === 'menunggu_tinjauan') {
            if ($hasStatusVerifikasi) {
                $claimsQuery->whereIn('status_verifikasi', [WorkflowStatus::CLAIM_SUBMITTED, WorkflowStatus::CLAIM_UNDER_REVIEW]);
            } else {
                $claimsQuery->where('status_klaim', 'pending');
            }
        } elseif ($status === 'tidak_disetujui') {
            if ($hasStatusVerifikasi) {
                $claimsQuery->where('status_verifikasi', WorkflowStatus::CLAIM_REJECTED);
            } else {
                $claimsQuery->where('status_klaim', 'ditolak');
            }
        } elseif ($status === 'sedang_diproses') {
            if ($hasStatusVerifikasi) {
                $claimsQuery->where('status_verifikasi', WorkflowStatus::CLAIM_APPROVED);
            } else {
                $claimsQuery->where('status_klaim', 'disetujui')->where(function ($builder) {
                    $builder->whereDoesntHave('barang')
                        ->orWhereHas('barang', function ($barangQuery) {
                            $barangQuery->where('status_barang', '!=', 'sudah_dikembalikan');
                        });
                });
            }
        } elseif ($status === 'selesai') {
            if ($hasStatusVerifikasi) {
                $claimsQuery->where('status_verifikasi', WorkflowStatus::CLAIM_COMPLETED);
            } else {
                $claimsQuery->where('status_klaim', 'disetujui')->whereHas('barang', function ($barangQuery) {
                    $barangQuery->where('status_barang', 'sudah_dikembalikan');
                });
            }
        }

        if ($type === 'temuan') {
            $claimsQuery->whereNotNull('barang_id');
        } elseif ($type === 'hilang') {
            $claimsQuery->whereNotNull('laporan_hilang_id');
        }

        $claims = $claimsQuery->paginate(8)->withQueryString();
        $claims->setCollection(
            $claims->getCollection()->map(function (Klaim $claim) use ($hasStatusVerifikasi) {
                [$statusText, $statusClass, $statusKey] = $this->resolveStatus($claim, $hasStatusVerifikasi);
                $statusDetail = $this->resolveStatusDetail($claim, $statusKey);

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
                    'status_detail' => $statusDetail,
                    'pickup_location' => $locationPickup,
                    'detail_url' => $this->resolveDetailUrl($claim),
                ];
            })
        );

        return [
            'claims' => $claims,
            'search' => $search,
            'statusFilter' => $status,
            'typeFilter' => $type,
        ];
    }

    public function deleteClaimProofs(Klaim $klaim): void
    {
        foreach ((array) ($klaim->bukti_foto ?? []) as $path) {
            if (is_string($path) && trim($path) !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function resolveStatus(Klaim $claim, bool $hasStatusVerifikasi): array
    {
        $key = ClaimStatusPresenter::key(
            statusKlaim: (string) $claim->status_klaim,
            statusVerifikasi: $hasStatusVerifikasi ? (string) ($claim->status_verifikasi ?? '') : null,
            statusBarang: (string) ($claim->barang?->status_barang ?? '')
        );

        return match ($key) {
            'ditolak' => ['Tidak Disetujui', 'status-ditolak', 'tidak_disetujui'],
            'disetujui' => ['Sedang Diproses', 'status-diproses', 'sedang_diproses'],
            'selesai' => ['Selesai', 'status-selesai', 'selesai'],
            default => ['Menunggu Tinjauan', 'status-dalam_peninjauan', 'menunggu_tinjauan'],
        };
    }

    private function resolvePickupLocation(Klaim $claim, string $statusKey): string
    {
        if ($statusKey === 'menunggu_tinjauan') {
            return 'Menunggu Tinjauan';
        }
        if ($statusKey === 'tidak_disetujui') {
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

    private function resolveStatusDetail(Klaim $claim, string $statusKey): string
    {
        if ($statusKey === 'menunggu_tinjauan') {
            return 'Klaim sedang diperiksa admin. Pastikan bukti kepemilikan sudah lengkap.';
        }
        if ($statusKey === 'tidak_disetujui') {
            return 'Klaim ditolak. Periksa notifikasi dan lengkapi bukti untuk pengajuan berikutnya.';
        }
        if ($statusKey === 'selesai') {
            return 'Barang sudah diserahkan dan proses klaim dinyatakan selesai.';
        }

        $petugas = trim((string) ($claim->barang?->penanggung_jawab_pengambilan ?? ''));
        $kontak = trim((string) ($claim->barang?->kontak_pengambilan ?? ''));
        $jamLayanan = trim((string) ($claim->barang?->jam_layanan_pengambilan ?? ''));

        $pieces = array_values(array_filter([
            $petugas !== '' ? ('Petugas: ' . $petugas) : null,
            $kontak !== '' ? ('Kontak: ' . $kontak) : null,
            $jamLayanan !== '' ? ('Jam: ' . $jamLayanan) : null,
        ]));

        if ($pieces === []) {
            return 'Klaim disetujui. Lihat detail barang untuk informasi pengambilan.';
        }

        return 'Klaim disetujui. ' . implode(' | ', $pieces);
    }

    private function resolveDetailUrl(Klaim $claim): string
    {
        if (!is_null($claim->barang_id)) {
            return route('home.found-detail', $claim->barang_id);
        }

        if (!is_null($claim->laporan_hilang_id)) {
            return route('home.lost-detail', $claim->laporan_hilang_id);
        }

        return route('home');
    }

    private function resolveItemImageUrl(string $fotoPath, string $defaultFolder): string
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

        if ($subPath !== '') {
            return route('media.image', ['folder' => $defaultFolder, 'path' => $cleanPath]);
        }

        return asset('storage/' . $cleanPath);
    }
}
