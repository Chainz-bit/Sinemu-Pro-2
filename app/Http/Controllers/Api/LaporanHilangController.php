<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLaporanHilangRequest;
use App\Http\Requests\Api\UpdateLaporanHilangRequest;
use App\Http\Resources\Api\LaporanResource;
use App\Models\Kategori;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Services\ReportImageCleaner;
use App\Support\WorkflowStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LaporanHilangController extends Controller
{
    public function store(StoreLaporanHilangRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $kategori = Kategori::query()->findOrFail((int) $validated['kategori_id']);
        $photoPath = null;

        if ($request->hasFile('foto')) {
            $photoPath = $request->file('foto')->store('barang-hilang/'.now()->format('Y/m'), 'public');
        }

        $payload = [
            'user_id' => (int) $request->user()->id,
            'region_id' => (int) $validated['wilayah_id'],
            'kategori_id' => (int) $kategori->id,
            'kategori_barang' => $kategori->nama_kategori,
            'nama_barang' => $validated['nama_barang'],
            'lokasi_hilang' => $validated['lokasi'],
            'tanggal_hilang' => $validated['tanggal_hilang'],
            'keterangan' => $validated['deskripsi'],
            'kontak_pelapor' => $request->user()->nomor_telepon,
            'foto_barang' => $photoPath,
            'sumber_laporan' => 'lapor_hilang',
            'tampil_di_home' => false,
            'status_laporan' => WorkflowStatus::REPORT_SUBMITTED,
            'verified_by_admin_id' => null,
            'verified_at' => null,
        ];

        try {
            $laporan = LaporanBarangHilang::query()->create($payload);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Laporan barang hilang berhasil dibuat',
            'data' => new LaporanResource($laporan->load(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])),
        ], 201);
    }

    public function show(LaporanBarangHilang $laporanBarangHilang): LaporanResource
    {
        $this->ensureOwner($laporanBarangHilang);

        return new LaporanResource($laporanBarangHilang->load(['kategori:id,nama_kategori', 'region:id,nama_wilayah']));
    }

    public function update(UpdateLaporanHilangRequest $request, LaporanBarangHilang $laporanBarangHilang): JsonResponse
    {
        $this->ensureOwner($laporanBarangHilang);
        $this->ensureEditable($laporanBarangHilang);

        $validated = $request->validated();
        $payload = [];

        if (array_key_exists('nama_barang', $validated)) {
            $payload['nama_barang'] = $validated['nama_barang'];
        }

        if (array_key_exists('wilayah_id', $validated)) {
            $payload['region_id'] = (int) $validated['wilayah_id'];
        }

        if (array_key_exists('kategori_id', $validated)) {
            $kategori = Kategori::query()->findOrFail((int) $validated['kategori_id']);
            $payload['kategori_id'] = (int) $kategori->id;
            $payload['kategori_barang'] = $kategori->nama_kategori;
        }

        if (array_key_exists('lokasi', $validated)) {
            $payload['lokasi_hilang'] = $validated['lokasi'];
        }

        if (array_key_exists('deskripsi', $validated)) {
            $payload['keterangan'] = $validated['deskripsi'];
        }

        if (array_key_exists('tanggal_hilang', $validated)) {
            $payload['tanggal_hilang'] = $validated['tanggal_hilang'];
        }

        $newPhotoPath = null;
        $oldPhotoPath = $laporanBarangHilang->foto_barang;
        if ($request->hasFile('foto')) {
            $newPhotoPath = $request->file('foto')->store('barang-hilang/'.now()->format('Y/m'), 'public');
            $payload['foto_barang'] = $newPhotoPath;
        }

        try {
            if ($payload !== []) {
                $laporanBarangHilang->update($payload);
            }

            if ($newPhotoPath && $oldPhotoPath) {
                ReportImageCleaner::purgeIfOrphaned($oldPhotoPath);
            }
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Laporan barang hilang berhasil diperbarui',
            'data' => new LaporanResource($laporanBarangHilang->refresh()->load(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])),
        ]);
    }

    public function destroy(LaporanBarangHilang $laporanBarangHilang): JsonResponse
    {
        $this->ensureOwner($laporanBarangHilang);
        $this->ensureEditable($laporanBarangHilang);

        $hasAnyClaim = Klaim::query()
            ->where('laporan_hilang_id', (int) $laporanBarangHilang->id)
            ->exists();

        if ($hasAnyClaim) {
            abort(409, 'Laporan yang sudah diproses tidak bisa dihapus.');
        }

        $photoPath = $laporanBarangHilang->foto_barang;
        $laporanBarangHilang->delete();
        ReportImageCleaner::purgeIfOrphaned($photoPath);

        return response()->json([
            'message' => 'Laporan barang hilang berhasil dihapus',
        ]);
    }

    private function ensureOwner(LaporanBarangHilang $laporan): void
    {
        if ((int) $laporan->user_id !== (int) request()->user()->id) {
            abort(403, 'Tidak punya akses untuk data ini.');
        }
    }

    private function ensureEditable(LaporanBarangHilang $laporan): void
    {
        if (($laporan->sumber_laporan ?? 'lapor_hilang') !== 'lapor_hilang') {
            abort(409, 'Laporan ini tidak bisa diubah dari mobile.');
        }

        if (! in_array((string) ($laporan->status_laporan ?? WorkflowStatus::REPORT_SUBMITTED), [
            WorkflowStatus::REPORT_SUBMITTED,
            WorkflowStatus::REPORT_REJECTED,
        ], true)) {
            abort(409, 'Laporan tidak bisa diubah pada status saat ini.');
        }

        $hasBlockingClaim = Klaim::query()
            ->where('laporan_hilang_id', (int) $laporan->id)
            ->whereIn('status_klaim', [
                WorkflowStatus::CLAIM_LEGACY_PENDING,
                WorkflowStatus::CLAIM_LEGACY_APPROVED,
            ])
            ->exists();

        if ($hasBlockingClaim) {
            abort(409, 'Laporan yang sedang diproses tidak bisa diubah.');
        }
    }
}
