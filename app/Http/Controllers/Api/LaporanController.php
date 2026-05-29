<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LaporanResource;
use App\Models\Barang;
use App\Models\LaporanBarangHilang;
use App\Support\WorkflowStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaporanController extends Controller
{
    private const PUBLIC_REPORT_STATUSES = [
        WorkflowStatus::REPORT_APPROVED,
        'verified',
        WorkflowStatus::REPORT_MATCHED,
        WorkflowStatus::REPORT_CLAIMED,
    ];

    public function index(): AnonymousResourceCollection
    {
        $userId = (int) request()->user()->id;

        $laporanHilang = LaporanBarangHilang::query()
            ->with(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])
            ->where('user_id', $userId)
            ->get();

        $laporanTemuan = Barang::query()
            ->with(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])
            ->where('user_id', $userId)
            ->get();

        $laporan = $laporanHilang
            ->concat($laporanTemuan)
            ->sortByDesc(fn ($item) => $item->created_at?->timestamp ?? 0)
            ->values();

        return LaporanResource::collection($laporan);
    }

    public function publik(): AnonymousResourceCollection
    {
        $laporanHilang = LaporanBarangHilang::query()
            ->with(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])
            ->whereIn('status_laporan', self::PUBLIC_REPORT_STATUSES)
            ->where('tampil_di_home', true)
            ->whereNotNull('verified_by_admin_id')
            ->whereNotNull('verified_at')
            ->get();

        $laporanTemuan = Barang::query()
            ->with(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])
            ->whereIn('status_laporan', self::PUBLIC_REPORT_STATUSES)
            ->where('tampil_di_home', true)
            ->whereNotNull('verified_by_admin_id')
            ->whereNotNull('verified_at')
            ->get();

        $laporan = $laporanHilang
            ->concat($laporanTemuan)
            ->sortByDesc(fn ($item) => $item->updated_at?->timestamp ?? $item->created_at?->timestamp ?? 0)
            ->values();

        return LaporanResource::collection($laporan);
    }
}
