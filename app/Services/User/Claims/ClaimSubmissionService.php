<?php

namespace App\Services\User\Claims;

use App\Models\Barang;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Models\Pencocokan;
use App\Support\WorkflowStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ClaimSubmissionService
{
    /**
     * @return array{ok:bool,message:string}
     */
    public function submit(Request $request): array
    {
        $user = Auth::user();
        if (!$user) {
            return ['ok' => false, 'message' => 'Anda harus login sebelum mengajukan klaim.'];
        }

        $validated = $request->validate([
            'barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'laporan_hilang_id' => ['required', 'integer', 'exists:laporan_barang_hilangs,id'],
            'kontak_pelapor' => ['required', 'string', 'max:50'],
            'bukti_kepemilikan' => ['required', 'string', 'max:2000'],
            'bukti_ciri_khusus' => ['required', 'string', 'max:2000'],
            'bukti_detail_isi' => ['nullable', 'string', 'max:2000'],
            'bukti_lokasi_spesifik' => ['required', 'string', 'max:255'],
            'bukti_waktu_hilang' => ['required', 'date_format:H:i'],
            'bukti_foto' => ['required', 'array', 'min:1', 'max:3'],
            'bukti_foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'catatan' => ['nullable', 'string'],
            'persetujuan_klaim' => ['accepted'],
        ]);

        $barang = Barang::query()->select('id', 'admin_id', 'status_barang')->find($validated['barang_id']);
        if (!$barang) {
            return ['ok' => false, 'message' => 'Barang temuan tidak ditemukan.'];
        }

        $hasDuplicateClaim = Klaim::query()
            ->where('user_id', (int) Auth::id())
            ->where('barang_id', (int) $barang->id)
            ->whereIn('status_klaim', ['pending', 'disetujui'])
            ->exists();
        if ($hasDuplicateClaim) {
            return ['ok' => false, 'message' => 'Anda sudah pernah mengajukan klaim aktif untuk barang ini.'];
        }

        $laporan = LaporanBarangHilang::query()
            ->where('id', (int) $validated['laporan_hilang_id'])
            ->where('user_id', (int) Auth::id())
            ->first();

        if (!$laporan) {
            return ['ok' => false, 'message' => 'Pilih laporan barang hilang milik Anda yang valid sebelum mengajukan klaim.'];
        }

        if (Schema::hasColumn('laporan_barang_hilangs', 'status_laporan')
            && !in_array((string) $laporan->status_laporan, [WorkflowStatus::REPORT_APPROVED, WorkflowStatus::REPORT_MATCHED, WorkflowStatus::REPORT_CLAIMED], true)) {
            return ['ok' => false, 'message' => 'Laporan barang hilang harus disetujui admin terlebih dahulu sebelum klaim.'];
        }

        $pencocokan = Pencocokan::query()
            ->where('laporan_hilang_id', (int) $laporan->id)
            ->where('barang_id', (int) $barang->id)
            ->whereIn('status_pencocokan', [WorkflowStatus::MATCH_CONFIRMED, WorkflowStatus::MATCH_CLAIM_IN_PROGRESS, WorkflowStatus::MATCH_CLAIM_REJECTED])
            ->latest('updated_at')
            ->first();

        if (!$pencocokan) {
            return ['ok' => false, 'message' => 'Barang ini belum ditandai cocok oleh admin dengan laporan Anda.'];
        }

        $hasBlockingClaimForReport = Klaim::query()
            ->where('laporan_hilang_id', (int) $laporan->id)
            ->whereIn('status_klaim', ['pending', 'disetujui'])
            ->exists();
        if ($hasBlockingClaimForReport) {
            return ['ok' => false, 'message' => 'Laporan ini masih punya klaim aktif. Tunggu proses klaim sebelumnya selesai.'];
        }

        $laporanUpdatePayload = [];
        if (empty($laporan->kontak_pelapor) && !empty($validated['kontak_pelapor'])) {
            $laporanUpdatePayload['kontak_pelapor'] = $validated['kontak_pelapor'];
        }
        if (empty($laporan->bukti_kepemilikan) && !empty($validated['bukti_kepemilikan'])) {
            $laporanUpdatePayload['bukti_kepemilikan'] = $validated['bukti_kepemilikan'];
        }
        if ($laporanUpdatePayload !== []) {
            $laporan->update($laporanUpdatePayload);
        }

        $buktiFotoPaths = [];
        foreach (($request->file('bukti_foto') ?? []) as $photo) {
            $buktiFotoPaths[] = $photo->store('verifikasi-klaim/' . now()->format('Y/m'), 'public');
        }

        $claimPayload = [
            'laporan_hilang_id' => (int) $laporan->id,
            'barang_id' => (int) $barang->id,
            'pencocokan_id' => (int) $pencocokan->id,
            'user_id' => (int) Auth::id(),
            'admin_id' => (int) $barang->admin_id,
            'status_klaim' => 'pending',
            'catatan' => $validated['catatan'] ?? null,
            'bukti_foto' => $buktiFotoPaths,
        ];
        if (Schema::hasColumn('klaims', 'status_verifikasi')) {
            $claimPayload['status_verifikasi'] = WorkflowStatus::CLAIM_UNDER_REVIEW;
        }
        if (Schema::hasColumn('klaims', 'bukti_ciri_khusus')) {
            $claimPayload['bukti_ciri_khusus'] = $validated['bukti_ciri_khusus'];
        }
        if (Schema::hasColumn('klaims', 'bukti_detail_isi')) {
            $claimPayload['bukti_detail_isi'] = $validated['bukti_detail_isi'] ?? null;
        }
        if (Schema::hasColumn('klaims', 'bukti_lokasi_spesifik')) {
            $claimPayload['bukti_lokasi_spesifik'] = $validated['bukti_lokasi_spesifik'];
        }
        if (Schema::hasColumn('klaims', 'bukti_waktu_hilang')) {
            $claimPayload['bukti_waktu_hilang'] = $validated['bukti_waktu_hilang'];
        }

        Klaim::create($claimPayload);

        if ($barang->status_barang === 'tersedia') {
            $barang->update(['status_barang' => 'dalam_proses_klaim']);
        }
        if (Schema::hasColumn('laporan_barang_hilangs', 'status_laporan')) {
            $laporan->update(['status_laporan' => WorkflowStatus::REPORT_CLAIMED]);
        }
        $pencocokan->update(['status_pencocokan' => WorkflowStatus::MATCH_CLAIM_IN_PROGRESS]);

        return ['ok' => true, 'message' => 'Pengajuan klaim berhasil dikirim. Pantau status verifikasi di Riwayat Klaim.'];
    }
}
