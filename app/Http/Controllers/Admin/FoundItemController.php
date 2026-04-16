<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangStatusHistory;
use App\Models\Kategori;
use App\Services\ReportImageCleaner;
use App\Services\UserNotificationService;
use App\Support\Media\OptimizedImageUploader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FoundItemController extends Controller
{
    public function __construct(private readonly OptimizedImageUploader $imageUploader)
    {
    }

    public function index(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $query = Barang::query()
            ->with(['kategori', 'admin:id,nama'])
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where('nama_barang', 'like', '%'.$search.'%');
        }

        if ($request->filled('status')) {
            $allowedStatus = ['tersedia', 'dalam_proses_klaim', 'sudah_diklaim', 'sudah_dikembalikan'];
            $status = (string) $request->query('status');
            if (in_array($status, $allowedStatus, true)) {
                $query->where('status_barang', $status);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('tanggal_ditemukan', $request->query('date'));
        }

        $sort = (string) $request->query('sort', 'terbaru');
        switch ($sort) {
            case 'terlama':
                $query->orderBy('updated_at');
                break;
            case 'nama_asc':
                $query->orderBy('nama_barang');
                break;
            case 'nama_desc':
                $query->orderByDesc('nama_barang');
                break;
            default:
                $query->orderByDesc('updated_at');
                break;
        }

        if ($request->boolean('export')) {
            $exportItems = $query->get();

            return new StreamedResponse(function () use ($exportItems) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Nama Barang', 'Kategori', 'Tanggal Ditemukan', 'Lokasi', 'Status']);

                foreach ($exportItems as $item) {
                    fputcsv($handle, [
                        $item->nama_barang,
                        $item->kategori?->nama_kategori ?? 'Tanpa Kategori',
                        $item->tanggal_ditemukan,
                        $item->lokasi_ditemukan,
                        $item->status_barang,
                    ]);
                }

                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="barang-temuan.csv"',
            ]);
        }

        $items = $query->paginate(12)->withQueryString();

        return view('admin.pages.found-items', compact('items', 'admin', 'sort'));
    }

    public function show(Barang $barang): View
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $barang->loadMissing([
            'kategori:id,nama_kategori',
            'admin:id,nama,email',
            'statusHistories.admin:id,nama',
        ]);

        return view('admin.pages.found-item-detail', compact('barang', 'admin'));
    }

    public function edit(Barang $barang): View
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();
        $kategoriOptions = Kategori::query()
            ->orderBy('nama_kategori')
            ->get(['id', 'nama_kategori']);

        return view('admin.pages.found-item-edit', compact('barang', 'admin', 'kategoriOptions'));
    }

    public function update(Request $request, Barang $barang): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategoris,id'],
            'warna_barang' => ['nullable', 'string', 'max:100'],
            'merek_barang' => ['nullable', 'string', 'max:120'],
            'nomor_seri' => ['nullable', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'ciri_khusus' => ['nullable', 'string', 'max:2000'],
            'nama_penemu' => ['nullable', 'string', 'max:150'],
            'kontak_penemu' => ['nullable', 'string', 'max:50'],
            'lokasi_ditemukan' => ['required', 'string', 'max:255'],
            'detail_lokasi_ditemukan' => ['nullable', 'string', 'max:2000'],
            'tanggal_ditemukan' => ['required', 'date'],
            'waktu_ditemukan' => ['nullable', 'date_format:H:i'],
            'status_barang' => ['required', 'in:tersedia,dalam_proses_klaim,sudah_diklaim,sudah_dikembalikan'],
            'lokasi_pengambilan' => ['nullable', 'string', 'max:255'],
            'alamat_pengambilan' => ['nullable', 'string', 'max:255'],
            'penanggung_jawab_pengambilan' => ['nullable', 'string', 'max:255'],
            'kontak_pengambilan' => ['nullable', 'string', 'max:255'],
            'jam_layanan_pengambilan' => ['nullable', 'string', 'max:255'],
            'catatan_pengambilan' => ['nullable', 'string', 'max:2000'],
            'foto_barang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $payload = [
            'nama_barang' => $validated['nama_barang'],
            'kategori_id' => $validated['kategori_id'] ?? $barang->kategori_id,
            'warna_barang' => $validated['warna_barang'] ?? null,
            'merek_barang' => $validated['merek_barang'] ?? null,
            'nomor_seri' => $validated['nomor_seri'] ?? null,
            'deskripsi' => isset($validated['deskripsi']) && trim((string) $validated['deskripsi']) !== ''
                ? trim((string) $validated['deskripsi'])
                : ((string) ($barang->deskripsi ?? '')),
            'ciri_khusus' => isset($validated['ciri_khusus']) && trim((string) $validated['ciri_khusus']) !== ''
                ? trim((string) $validated['ciri_khusus'])
                : null,
            'nama_penemu' => isset($validated['nama_penemu']) && trim((string) $validated['nama_penemu']) !== ''
                ? trim((string) $validated['nama_penemu'])
                : null,
            'kontak_penemu' => isset($validated['kontak_penemu']) && trim((string) $validated['kontak_penemu']) !== ''
                ? trim((string) $validated['kontak_penemu'])
                : null,
            'lokasi_ditemukan' => $validated['lokasi_ditemukan'],
            'detail_lokasi_ditemukan' => isset($validated['detail_lokasi_ditemukan']) && trim((string) $validated['detail_lokasi_ditemukan']) !== ''
                ? trim((string) $validated['detail_lokasi_ditemukan'])
                : null,
            'tanggal_ditemukan' => $validated['tanggal_ditemukan'],
            'waktu_ditemukan' => $validated['waktu_ditemukan'] ?? null,
            'status_barang' => $validated['status_barang'],
            'lokasi_pengambilan' => isset($validated['lokasi_pengambilan']) && trim((string) $validated['lokasi_pengambilan']) !== ''
                ? trim((string) $validated['lokasi_pengambilan'])
                : null,
            'alamat_pengambilan' => isset($validated['alamat_pengambilan']) && trim((string) $validated['alamat_pengambilan']) !== ''
                ? trim((string) $validated['alamat_pengambilan'])
                : null,
            'penanggung_jawab_pengambilan' => isset($validated['penanggung_jawab_pengambilan']) && trim((string) $validated['penanggung_jawab_pengambilan']) !== ''
                ? trim((string) $validated['penanggung_jawab_pengambilan'])
                : null,
            'kontak_pengambilan' => isset($validated['kontak_pengambilan']) && trim((string) $validated['kontak_pengambilan']) !== ''
                ? trim((string) $validated['kontak_pengambilan'])
                : null,
            'jam_layanan_pengambilan' => isset($validated['jam_layanan_pengambilan']) && trim((string) $validated['jam_layanan_pengambilan']) !== ''
                ? trim((string) $validated['jam_layanan_pengambilan'])
                : null,
            'catatan_pengambilan' => isset($validated['catatan_pengambilan']) && trim((string) $validated['catatan_pengambilan']) !== ''
                ? trim((string) $validated['catatan_pengambilan'])
                : null,
        ];

        $oldPhotoPath = null;
        $photo = $request->file('foto_barang');
        if ($photo) {
            $oldPhotoPath = $barang->foto_barang;
            $payload['foto_barang'] = $this->imageUploader->upload($photo, 'barang-temuan/' . now()->format('Y/m'));
        }

        $barang->update($payload);

        if (!empty($oldPhotoPath)) {
            ReportImageCleaner::purgeIfOrphaned($oldPhotoPath);
        }

        return redirect()
            ->route('admin.found-items.show', $barang->id)
            ->with('status', 'Data barang temuan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Barang $barang): RedirectResponse
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'status_barang' => ['required', 'in:tersedia,dalam_proses_klaim,sudah_diklaim,sudah_dikembalikan'],
            'catatan_status' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = (string) $barang->status_barang;
        $newStatus = (string) $validated['status_barang'];

        if ($oldStatus !== $newStatus) {
            $barang->update(['status_barang' => $newStatus]);

            BarangStatusHistory::create([
                'barang_id' => $barang->id,
                'admin_id' => $admin?->id,
                'status_lama' => $oldStatus,
                'status_baru' => $newStatus,
                'catatan' => $validated['catatan_status'] ?? null,
            ]);

            $statusLabel = match ($newStatus) {
                'tersedia' => 'Tersedia',
                'dalam_proses_klaim' => 'Dalam Proses Klaim',
                'sudah_diklaim' => 'Sudah Diklaim',
                'sudah_dikembalikan' => 'Sudah Dikembalikan',
                default => $newStatus,
            };

            $barang->klaims()
                ->select('user_id')
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id')
                ->each(function ($userId) use ($barang, $statusLabel) {
                    UserNotificationService::notifyUser(
                        userId: (int) $userId,
                        type: 'status_barang_temuan',
                        title: 'Status Barang Temuan Diperbarui',
                        message: 'Admin memperbarui status '.$barang->nama_barang.' menjadi '.$statusLabel.'.',
                        actionUrl: route('user.dashboard'),
                        meta: ['barang_id' => $barang->id]
                    );
                });

            return redirect()
                ->route('admin.found-items.show', $barang->id)
                ->with('status', 'Perubahan status berhasil disimpan.');
        }

        return redirect()
            ->route('admin.found-items.show', $barang->id)
            ->with('status', 'Tidak ada perubahan status yang disimpan.');
    }

    public function export(Barang $barang): Response
    {
        $barang->loadMissing(['kategori:id,nama_kategori', 'admin:id,nama,email']);

        $statusLabel = match ($barang->status_barang) {
            'tersedia' => 'Tersedia',
            'dalam_proses_klaim' => 'Dalam Proses Klaim',
            'sudah_diklaim' => 'Sudah Diklaim',
            'sudah_dikembalikan' => 'Sudah Dikembalikan',
            default => 'Tidak Diketahui',
        };

        $photoDataUri = null;
        if (!empty($barang->foto_barang) && Storage::disk('public')->exists($barang->foto_barang)) {
            $absolutePath = Storage::disk('public')->path($barang->foto_barang);
            $mimeType = Storage::disk('public')->mimeType($barang->foto_barang) ?: 'image/jpeg';
            $photoDataUri = 'data:' . $mimeType . ';base64,' . base64_encode((string) file_get_contents($absolutePath));
        }

        $pdf = Pdf::loadView('admin.pdf.found-item-report', [
            'barang' => $barang,
            'statusLabel' => $statusLabel,
            'photoDataUri' => $photoDataUri,
        ])->setPaper('a4');

        return $pdf->download('laporan-barang-temuan-' . $barang->id . '.pdf');
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        $photoPath = $barang->foto_barang;

        $barang->delete();
        ReportImageCleaner::purgeIfOrphaned($photoPath);

        return redirect()->back()->with('status', 'Laporan barang temuan berhasil dihapus.');
    }
}
