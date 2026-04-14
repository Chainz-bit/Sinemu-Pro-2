<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LostItemIndexRequest;
use App\Models\Barang;
use App\Models\LaporanBarangHilang;
use App\Services\Admin\LostItems\LostItemExportService;
use App\Services\Admin\LostItems\LostItemQueryService;
use App\Services\ReportImageCleaner;
use App\Support\Media\OptimizedImageUploader;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LostItemController extends Controller
{
    public function __construct(
        private readonly LostItemQueryService $queryService,
        private readonly LostItemExportService $exportService,
        private readonly OptimizedImageUploader $imageUploader,
    ) {
    }

    public function index(LostItemIndexRequest $request): View|StreamedResponse
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $query = $this->queryService->buildIndexQuery($request);
        $sort = $request->sort();

        if ($request->shouldExport()) {
            return $this->exportService->exportCsv($query->get());
        }

        $items = $query->paginate(12)->withQueryString();

        return view('admin.pages.lost-items', compact('items', 'admin', 'sort'));
    }

    public function show(LaporanBarangHilang $laporanBarangHilang): View|RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            return redirect()->route('admin.lost-items');
        }

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $laporanBarangHilang->loadMissing(['user:id,nama,name,email', 'klaims' => function ($query) {
            $query->latest('created_at');
        }]);

        $latestKlaim = $laporanBarangHilang->klaims->first();

        return view('admin.pages.lost-item-detail', compact('laporanBarangHilang', 'latestKlaim', 'admin'));
    }

    public function edit(LaporanBarangHilang $laporanBarangHilang): View|RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            return redirect()->route('admin.lost-items');
        }

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('admin.pages.lost-item-edit', compact('laporanBarangHilang', 'admin'));
    }

    public function update(Request $request, LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            abort(404);
        }

        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'lokasi_hilang' => ['required', 'string', 'max:255'],
            'tanggal_hilang' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'foto_barang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $payload = [
            'nama_barang' => $validated['nama_barang'],
            'lokasi_hilang' => $validated['lokasi_hilang'],
            'tanggal_hilang' => $validated['tanggal_hilang'],
            'keterangan' => isset($validated['keterangan']) && trim((string) $validated['keterangan']) !== ''
                ? trim((string) $validated['keterangan'])
                : null,
        ];

        $oldPhotoPath = null;
        $photo = $request->file('foto_barang');
        if ($photo) {
            $oldPhotoPath = $laporanBarangHilang->foto_barang;
            $payload['foto_barang'] = $this->imageUploader->upload($photo, 'barang-hilang/' . now()->format('Y/m'));
        }

        $laporanBarangHilang->update($payload);

        if (!empty($oldPhotoPath)) {
            ReportImageCleaner::purgeIfOrphaned($oldPhotoPath);
        }

        return redirect()
            ->route('admin.lost-items.show', $laporanBarangHilang->id)
            ->with('status', 'Data barang hilang berhasil diperbarui.');
    }

    public function destroy(LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            abort_if($laporanBarangHilang->sumber_laporan !== 'lapor_hilang', 404);
        }

        $photoPath = $laporanBarangHilang->foto_barang;

        $laporanBarangHilang->delete();
        ReportImageCleaner::purgeIfOrphaned($photoPath);

        return redirect()->back()->with('status', 'Laporan barang hilang berhasil dihapus.');
    }

    public function updateStatus(Request $request, LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        $validated = $request->validate([
            'status_klaim' => ['required', 'in:pending,disetujui,ditolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $latestKlaim = $laporanBarangHilang->klaims()->latest('created_at')->first();
        if (!$latestKlaim) {
            $candidateBarang = Barang::query()
                ->where('status_barang', '!=', 'sudah_diklaim')
                ->where(function ($query) use ($laporanBarangHilang) {
                    $query
                        ->where('nama_barang', 'like', '%'.$laporanBarangHilang->nama_barang.'%')
                        ->orWhere('deskripsi', 'like', '%'.$laporanBarangHilang->nama_barang.'%');
                })
                ->orderByRaw("CASE WHEN status_barang = 'dalam_proses_klaim' THEN 0 WHEN status_barang = 'tersedia' THEN 1 ELSE 2 END")
                ->latest('updated_at')
                ->first();

            if (!$candidateBarang) {
                return back()->with('error', 'Belum ada barang temuan yang cocok untuk laporan ini. Tambahkan/tautkan barang temuan terlebih dahulu.');
            }

            $latestKlaim = $laporanBarangHilang->klaims()->create([
                'barang_id' => $candidateBarang->id,
                'user_id' => (int) $laporanBarangHilang->user_id,
                'admin_id' => (int) Auth::guard('admin')->id(),
                'status_klaim' => $validated['status_klaim'],
                'catatan' => $validated['catatan'] ?? null,
            ]);
        } else {
            $latestKlaim->update([
                'status_klaim' => $validated['status_klaim'],
                'catatan' => $validated['catatan'] ?? null,
                'admin_id' => (int) Auth::guard('admin')->id(),
            ]);
        }

        if ($latestKlaim->barang) {
            if ($validated['status_klaim'] === 'disetujui') {
                $latestKlaim->barang->update(['status_barang' => 'sudah_diklaim']);
            } elseif ($validated['status_klaim'] === 'ditolak' && $latestKlaim->barang->status_barang === 'dalam_proses_klaim') {
                $latestKlaim->barang->update(['status_barang' => 'tersedia']);
            }
        }

        return back()->with('status', 'Status barang hilang berhasil diperbarui.');
    }
}
