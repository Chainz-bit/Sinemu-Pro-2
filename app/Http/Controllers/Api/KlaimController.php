<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreKlaimRequest;
use App\Http\Resources\Api\KlaimResource;
use App\Models\Barang;
use App\Models\Klaim;
use App\Support\WorkflowStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class KlaimController extends Controller
{
    public function store(StoreKlaimRequest $request, Barang $barang): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $validated = $request->validated();

        if ((int) $barang->user_id === $userId) {
            abort(403, 'Tidak bisa mengklaim barang temuan milik sendiri.');
        }

        if ((string) $barang->status_barang !== WorkflowStatus::FOUND_AVAILABLE) {
            abort(409, 'Barang sudah diklaim atau sedang diproses.');
        }

        if (! in_array((string) ($barang->status_laporan ?? ''), [
            WorkflowStatus::REPORT_APPROVED,
            WorkflowStatus::REPORT_MATCHED,
        ], true)) {
            abort(403, 'Barang temuan belum bisa diklaim.');
        }

        if (! $barang->admin_id) {
            abort(409, 'Barang belum memiliki pengelola untuk memproses klaim.');
        }

        $hasUserClaim = Klaim::query()
            ->where('barang_id', (int) $barang->id)
            ->where('user_id', $userId)
            ->exists();

        if ($hasUserClaim) {
            abort(409, 'Klaim sudah pernah diajukan.');
        }

        $hasActiveClaim = Klaim::query()
            ->where('barang_id', (int) $barang->id)
            ->whereIn('status_klaim', [
                WorkflowStatus::CLAIM_LEGACY_PENDING,
                WorkflowStatus::CLAIM_LEGACY_APPROVED,
            ])
            ->exists();

        if ($hasActiveClaim) {
            abort(409, 'Barang sudah diklaim atau sedang diproses.');
        }

        $klaim = DB::transaction(function () use ($request, $barang, $validated, $userId): Klaim {
            $klaim = Klaim::query()->create([
                'barang_id' => (int) $barang->id,
                'user_id' => $userId,
                'admin_id' => (int) $barang->admin_id,
                'status_klaim' => WorkflowStatus::CLAIM_LEGACY_PENDING,
                'status_verifikasi' => WorkflowStatus::CLAIM_UNDER_REVIEW,
                'catatan' => $validated['alasan'],
                'kontak' => $validated['kontak'] ?? $request->user()->nomor_telepon,
            ]);

            $barang->update([
                'status_barang' => WorkflowStatus::FOUND_CLAIM_IN_PROGRESS,
            ]);

            return $klaim;
        });

        return response()->json([
            'message' => 'Klaim berhasil diajukan',
            'data' => new KlaimResource($klaim->load(['barang:id,nama_barang', 'laporanHilang:id,nama_barang,kontak_pelapor', 'user:id,nomor_telepon'])),
        ], 201);
    }

    public function index(): AnonymousResourceCollection
    {
        $klaims = Klaim::query()
            ->where('user_id', (int) request()->user()->id)
            ->with(['barang:id,nama_barang', 'laporanHilang:id,nama_barang,kontak_pelapor', 'user:id,nomor_telepon'])
            ->latest('created_at')
            ->get();

        return KlaimResource::collection($klaims);
    }

    public function show(Klaim $klaim): KlaimResource
    {
        $this->ensureOwner($klaim);

        return new KlaimResource($klaim->load(['barang:id,nama_barang', 'laporanHilang:id,nama_barang,kontak_pelapor', 'user:id,nomor_telepon']));
    }

    private function ensureOwner(Klaim $klaim): void
    {
        if ((int) $klaim->user_id !== (int) request()->user()->id) {
            abort(403, 'Tidak punya akses untuk data ini.');
        }
    }
}
