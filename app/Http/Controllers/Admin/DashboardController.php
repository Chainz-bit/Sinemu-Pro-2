<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();
        $totalHilangQuery = LaporanBarangHilang::query();
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            $totalHilangQuery->where('sumber_laporan', 'lapor_hilang');
        }
        $totalHilang = $totalHilangQuery->count();

        $totalTemuan = Barang::count();

        $menungguVerifikasi = Klaim::where('status_klaim', 'pending')->count();

        $hilangQuery = LaporanBarangHilang::select('id', 'user_id', 'nama_barang', 'lokasi_hilang', 'created_at', 'tanggal_hilang')
            ->selectSub(
                Klaim::select('status_klaim')
                    ->whereColumn('laporan_hilang_id', 'laporan_barang_hilangs.id')
                    ->latest('created_at')
                    ->limit(1),
                'latest_claim_status'
            );

        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            $hilangQuery->where('sumber_laporan', 'lapor_hilang');
        }

        $hilang = $hilangQuery
            ->latest('created_at')
            ->limit(8)
            ->get();

        $temuan = Barang::select('id', 'admin_id', 'nama_barang', 'lokasi_ditemukan', 'tanggal_ditemukan', 'status_barang', 'created_at')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $latestReports = $hilang->map(function ($report) {
            $status = 'diproses';

            if ($report->latest_claim_status) {
                $klaimStatus = $report->latest_claim_status;

                if ($klaimStatus === 'disetujui') {
                    $status = 'selesai';
                } elseif ($klaimStatus === 'ditolak') {
                    $status = 'ditolak';
                } elseif ($klaimStatus === 'pending') {
                    $status = 'dalam_peninjauan';
                }
            }

            return (object) [
                'type' => 'hilang',
                'item_name' => $report->nama_barang,
                'item_detail' => 'Laporan Hilang - ' . $report->lokasi_hilang,
                'location' => $report->lokasi_hilang,
                'incident_date' => $report->tanggal_hilang,
                'created_at' => $report->created_at,
                'status' => $status,
                'avatar' => 'L',
                'avatar_class' => 'avatar-sand',
            ];
        })->merge(
            $temuan->map(function ($report) {
                $status = match ($report->status_barang) {
                    'tersedia' => 'diproses',
                    'dalam_proses_klaim' => 'dalam_peninjauan',
                    'sudah_diklaim', 'sudah_dikembalikan' => 'selesai',
                    default => 'diproses',
                };

                return (object) [
                    'type' => 'temuan',
                    'item_name' => $report->nama_barang,
                    'item_detail' => 'Barang Temuan - ' . $report->lokasi_ditemukan,
                    'location' => $report->lokasi_ditemukan,
                    'incident_date' => $report->tanggal_ditemukan,
                    'created_at' => $report->created_at,
                    'status' => $status,
                    'avatar' => 'T',
                    'avatar_class' => 'avatar-mint',
                ];
            })
        )->sortByDesc('created_at')->take(5)->values();

        return view('admin.pages.dashboard', compact(
            'totalHilang',
            'totalTemuan',
            'menungguVerifikasi',
            'latestReports',
            'admin'
        ));
    }
}
