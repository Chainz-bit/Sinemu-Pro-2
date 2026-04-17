<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LostItemIndexRequest;
use App\Models\Kategori;
use App\Models\LaporanBarangHilang;
use App\Services\Admin\Matching\MatchingService;
use App\Services\Admin\LostItems\LostItemExportService;
use App\Services\Admin\LostItems\LostItemQueryService;
use App\Services\ReportImageCleaner;
use App\Services\UserNotificationService;
use App\Support\WorkflowStatus;
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
        private readonly MatchingService $matchingService,
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
        $matchingCandidates = collect();
        if ((string) ($laporanBarangHilang->status_laporan ?? '') === WorkflowStatus::REPORT_APPROVED) {
            $matchingCandidates = $this->matchingService->findCandidatesForLostReport($laporanBarangHilang);
        }

        return view('admin.pages.lost-item-detail', compact('laporanBarangHilang', 'latestKlaim', 'admin', 'matchingCandidates'));
    }

    public function edit(LaporanBarangHilang $laporanBarangHilang): View|RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            return redirect()->route('admin.lost-items');
        }

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $lostCategoryOptions = Kategori::query()
            ->forForm()
            ->pluck('nama_kategori')
            ->filter()
            ->values();

        return view('admin.pages.lost-item-edit', compact('laporanBarangHilang', 'admin', 'lostCategoryOptions'));
    }

    public function update(Request $request, LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan') && $laporanBarangHilang->sumber_laporan !== 'lapor_hilang') {
            abort(404);
        }

        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_barang' => ['nullable', 'string', 'max:100'],
            'warna_barang' => ['nullable', 'string', 'max:100'],
            'merek_barang' => ['nullable', 'string', 'max:120'],
            'nomor_seri' => ['nullable', 'string', 'max:150'],
            'lokasi_hilang' => ['required', 'string', 'max:255'],
            'detail_lokasi_hilang' => ['nullable', 'string', 'max:2000'],
            'tanggal_hilang' => ['required', 'date'],
            'waktu_hilang' => ['nullable', 'date_format:H:i'],
            'keterangan' => ['required', 'string', 'max:2000'],
            'ciri_khusus' => ['nullable', 'string', 'max:2000'],
            'kontak_pelapor' => ['nullable', 'string', 'max:50'],
            'bukti_kepemilikan' => ['nullable', 'string', 'max:2000'],
            'foto_barang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $payload = [
            'nama_barang' => $validated['nama_barang'],
            'kategori_barang' => $validated['kategori_barang'] ?? null,
            'warna_barang' => $validated['warna_barang'] ?? null,
            'merek_barang' => $validated['merek_barang'] ?? null,
            'nomor_seri' => $validated['nomor_seri'] ?? null,
            'lokasi_hilang' => $validated['lokasi_hilang'],
            'detail_lokasi_hilang' => isset($validated['detail_lokasi_hilang']) && trim((string) $validated['detail_lokasi_hilang']) !== ''
                ? trim((string) $validated['detail_lokasi_hilang'])
                : null,
            'tanggal_hilang' => $validated['tanggal_hilang'],
            'waktu_hilang' => $validated['waktu_hilang'] ?? null,
            'keterangan' => isset($validated['keterangan']) && trim((string) $validated['keterangan']) !== ''
                ? trim((string) $validated['keterangan'])
                : null,
            'ciri_khusus' => isset($validated['ciri_khusus']) && trim((string) $validated['ciri_khusus']) !== ''
                ? trim((string) $validated['ciri_khusus'])
                : null,
            'kontak_pelapor' => isset($validated['kontak_pelapor']) && trim((string) $validated['kontak_pelapor']) !== ''
                ? trim((string) $validated['kontak_pelapor'])
                : null,
            'bukti_kepemilikan' => isset($validated['bukti_kepemilikan']) && trim((string) $validated['bukti_kepemilikan']) !== ''
                ? trim((string) $validated['bukti_kepemilikan'])
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
        $latestKlaim = $laporanBarangHilang->klaims()->latest('created_at')->first();
        if (!$latestKlaim) {
            return back()->with('error', 'Belum ada klaim aktif untuk laporan ini.');
        }
        return redirect()
            ->route('admin.claim-verifications.show', $latestKlaim->id)
            ->with('error', 'Perbarui status klaim dari halaman Verifikasi Klaim agar checklist keamanan tetap diterapkan.');
    }

    public function verify(Request $request, LaporanBarangHilang $laporanBarangHilang): RedirectResponse
    {
        $validated = $request->validate([
            'status_laporan' => ['required', 'in:approved,rejected'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!Schema::hasColumn('laporan_barang_hilangs', 'status_laporan')) {
            return back()->with('error', 'Status verifikasi laporan belum tersedia di database.');
        }

        $newStatus = $validated['status_laporan'] === 'approved'
            ? WorkflowStatus::REPORT_APPROVED
            : WorkflowStatus::REPORT_REJECTED;

        $laporanBarangHilang->update([
            'status_laporan' => $newStatus,
            'verified_by_admin_id' => (int) Auth::guard('admin')->id(),
            'verified_at' => now(),
            'tampil_di_home' => $newStatus === WorkflowStatus::REPORT_APPROVED,
        ]);

        if (!is_null($laporanBarangHilang->user_id)) {
            $label = $newStatus === WorkflowStatus::REPORT_APPROVED ? 'disetujui' : 'ditolak';
            UserNotificationService::notifyUser(
                userId: (int) $laporanBarangHilang->user_id,
                type: 'verifikasi_laporan_hilang',
                title: 'Verifikasi Laporan Hilang',
                message: 'Laporan barang hilang "'.$laporanBarangHilang->nama_barang.'" '.$label.' admin.',
                actionUrl: route('user.dashboard'),
                meta: ['laporan_hilang_id' => $laporanBarangHilang->id]
            );
        }

        return back()->with('status', 'Verifikasi laporan barang hilang berhasil diperbarui.');
    }
}
