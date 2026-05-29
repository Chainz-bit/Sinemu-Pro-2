<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLaporanTemuanRequest;
use App\Http\Resources\Api\LaporanResource;
use App\Models\Admin;
use App\Models\Barang;
use App\Support\WorkflowStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LaporanTemuanController extends Controller
{
    public function store(StoreLaporanTemuanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $admin = Admin::query()
            ->select(['id', 'region_id'])
            ->where('region_id', (int) $validated['wilayah_id'])
            ->where('status_verifikasi', Admin::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            abort(422, 'Wilayah ini belum memiliki pengelola aktif.');
        }

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $photoPath = $request->file('foto')->store('barang-temuan/'.now()->format('Y/m'), 'public');
        }

        $payload = [
            'admin_id' => (int) $admin->id,
            'region_id' => (int) $validated['wilayah_id'],
            'user_id' => (int) $request->user()->id,
            'kategori_id' => (int) $validated['kategori_id'],
            'nama_barang' => $validated['nama_barang'],
            'deskripsi' => $validated['deskripsi'],
            'nama_penemu' => $request->user()->nama ?? $request->user()->name,
            'kontak_penemu' => $request->user()->nomor_telepon,
            'lokasi_ditemukan' => $validated['lokasi'],
            'tanggal_ditemukan' => $validated['tanggal_ditemukan'],
            'status_barang' => WorkflowStatus::FOUND_AVAILABLE,
            'foto_barang' => $photoPath,
            'tampil_di_home' => false,
            'status_laporan' => WorkflowStatus::REPORT_SUBMITTED,
            'verified_by_admin_id' => null,
            'verified_at' => null,
        ];

        try {
            $barang = Barang::query()->create($payload);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Laporan barang temuan berhasil dibuat',
            'data' => new LaporanResource($barang->load(['kategori:id,nama_kategori', 'region:id,nama_wilayah'])),
        ], 201);
    }

    public function show(Barang $barang): LaporanResource
    {
        $this->ensureCanView($barang);

        return new LaporanResource($barang->load(['kategori:id,nama_kategori', 'region:id,nama_wilayah']));
    }

    private function ensureCanView(Barang $barang): void
    {
        if ((int) $barang->user_id === (int) request()->user()->id) {
            return;
        }

        if (in_array((string) ($barang->status_laporan ?? ''), [
            WorkflowStatus::REPORT_APPROVED,
            WorkflowStatus::REPORT_MATCHED,
            WorkflowStatus::REPORT_CLAIMED,
            WorkflowStatus::REPORT_COMPLETED,
        ], true)) {
            return;
        }

        abort(403, 'Tidak punya akses untuk data ini.');
    }
}
