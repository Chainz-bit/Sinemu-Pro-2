<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LostReportController extends Controller
{
    public function create(Request $request)
    {
        $userId = (int) Auth::id();
        $editingReport = null;
        $editId = (int) $request->query('edit', 0);

        if ($editId > 0) {
            $editingReport = LaporanBarangHilang::query()
                ->where('id', $editId)
                ->where('user_id', $userId)
                ->first();

            if ($editingReport) {
                if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $editingReport->sumber_laporan !== 'lapor_hilang') {
                    $editingReport = null;
                } else {
                    $hasBlockingClaim = Klaim::query()
                        ->where('laporan_hilang_id', (int) $editingReport->id)
                        ->whereIn('status_klaim', ['pending', 'disetujui'])
                        ->exists();
                    if ($hasBlockingClaim) {
                        $editingReport = null;
                    }
                }
            }
        }

        return view('user.pages.lost-report', [
            'user' => Auth::user(),
            'lostCategoryOptions' => Kategori::query()
                ->orderBy('nama_kategori')
                ->pluck('nama_kategori')
                ->filter()
                ->values(),
            'editingReport' => $editingReport,
        ]);
    }
}
