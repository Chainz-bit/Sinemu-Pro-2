<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Services\ReportImageCleaner;
use App\Support\WorkflowStatus;
use Illuminate\Http\RedirectResponse;
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
                ->forForm()
                ->pluck('nama_kategori')
                ->filter()
                ->values(),
            'editingReport' => $editingReport,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_id' => ['nullable', 'integer', 'exists:laporan_barang_hilangs,id'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_barang' => ['nullable', 'string', 'max:100'],
            'warna_barang' => ['nullable', 'string', 'max:100'],
            'merek_barang' => ['nullable', 'string', 'max:120'],
            'nomor_seri' => ['nullable', 'string', 'max:150'],
            'lokasi_hilang' => ['required', 'string', 'max:255'],
            'detail_lokasi_hilang' => ['nullable', 'string', 'max:2000'],
            'tanggal_hilang' => ['required', 'date'],
            'waktu_hilang' => ['nullable', 'date_format:H:i'],
            'keterangan' => ['required', 'string'],
            'ciri_khusus' => ['nullable', 'string', 'max:2000'],
            'kontak_pelapor' => ['required', 'string', 'max:50'],
            'bukti_kepemilikan' => ['nullable', 'string', 'max:2000'],
            'foto_barang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $reportId = isset($validated['report_id']) ? (int) $validated['report_id'] : null;
        $editingReport = null;
        if (!is_null($reportId)) {
            $editingReport = LaporanBarangHilang::query()
                ->where('id', $reportId)
                ->where('user_id', (int) Auth::id())
                ->first();

            if (!$editingReport) {
                return back()->with('error', 'Laporan tidak ditemukan atau bukan milik Anda.');
            }

            if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $editingReport->sumber_laporan !== 'lapor_hilang') {
                return back()->with('error', 'Laporan ini tidak bisa diubah dari form ini.');
            }

            $hasBlockingClaim = Klaim::query()
                ->where('laporan_hilang_id', (int) $editingReport->id)
                ->whereIn('status_klaim', ['pending', 'disetujui'])
                ->exists();
            if ($hasBlockingClaim) {
                return back()->with('error', 'Laporan yang sedang diproses tidak bisa diubah.');
            }
        }

        $payload = [
            'user_id' => (int) Auth::id(),
            'nama_barang' => $validated['nama_barang'],
            'kategori_barang' => $validated['kategori_barang'] ?? null,
            'warna_barang' => $validated['warna_barang'] ?? null,
            'merek_barang' => $validated['merek_barang'] ?? null,
            'nomor_seri' => $validated['nomor_seri'] ?? null,
            'lokasi_hilang' => $validated['lokasi_hilang'],
            'detail_lokasi_hilang' => $validated['detail_lokasi_hilang'] ?? null,
            'tanggal_hilang' => $validated['tanggal_hilang'],
            'waktu_hilang' => $validated['waktu_hilang'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'ciri_khusus' => $validated['ciri_khusus'] ?? null,
            'kontak_pelapor' => $validated['kontak_pelapor'] ?? null,
            'bukti_kepemilikan' => $validated['bukti_kepemilikan'] ?? null,
        ];

        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            $payload['sumber_laporan'] = 'lapor_hilang';
        }
        if (Schema::hasColumn('laporan_barang_hilangs', 'status_laporan')) {
            $payload['status_laporan'] = WorkflowStatus::REPORT_SUBMITTED;
            $payload['tampil_di_home'] = false;
        }

        $photo = $request->file('foto_barang');
        if ($photo) {
            $payload['foto_barang'] = $photo->store('barang-hilang/' . now()->format('Y/m'), 'public');
        }

        if ($editingReport) {
            $oldPhotoPath = $editingReport->foto_barang;
            if (!$photo) {
                unset($payload['foto_barang']);
            }

            $editingReport->update($payload);

            if ($photo && !empty($oldPhotoPath)) {
                ReportImageCleaner::purgeIfOrphaned($oldPhotoPath);
            }

            return back()->with('status', 'Laporan barang hilang berhasil diperbarui.');
        }

        LaporanBarangHilang::create($payload);

        return back()->with('status', 'Laporan barang hilang berhasil dikirim.');
    }

    public function destroy(LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        $userId = (int) Auth::id();
        abort_if((int) $laporanBarangHilang->user_id !== $userId, 403);

        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            return back()->with('error', 'Laporan ini tidak bisa dihapus.');
        }

        $hasAnyClaim = Klaim::query()
            ->where('laporan_hilang_id', (int) $laporanBarangHilang->id)
            ->exists();
        if ($hasAnyClaim) {
            return back()->with('error', 'Laporan yang sudah diproses tidak bisa dihapus.');
        }

        $photoPath = $laporanBarangHilang->foto_barang;
        $laporanBarangHilang->delete();
        ReportImageCleaner::purgeIfOrphaned($photoPath);

        return back()->with('status', 'Laporan berhasil dihapus.');
    }
}
