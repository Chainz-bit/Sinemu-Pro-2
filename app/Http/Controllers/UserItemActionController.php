<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use App\Services\ReportImageCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserItemActionController extends Controller
{
    public function storeLostReport(Request $request): RedirectResponse
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

    public function destroyLostReport(LaporanBarangHilang $laporanBarangHilang): RedirectResponse
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

    public function storeFoundReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategoris,id'],
            'warna_barang' => ['nullable', 'string', 'max:100'],
            'merek_barang' => ['nullable', 'string', 'max:120'],
            'nomor_seri' => ['nullable', 'string', 'max:150'],
            'deskripsi' => ['required', 'string'],
            'ciri_khusus' => ['nullable', 'string', 'max:2000'],
            'nama_penemu' => ['nullable', 'string', 'max:150'],
            'kontak_penemu' => ['required', 'string', 'max:50'],
            'lokasi_ditemukan' => ['required', 'string', 'max:255'],
            'detail_lokasi_ditemukan' => ['nullable', 'string', 'max:2000'],
            'tanggal_ditemukan' => ['required', 'date'],
            'waktu_ditemukan' => ['nullable', 'date_format:H:i'],
            'foto_barang' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $admin = Admin::query()->select('id')->orderBy('id')->first();
        if (!$admin) {
            return back()->with('error', 'Belum ada admin aktif untuk menerima laporan temuan.');
        }

        $kategoriId = $validated['kategori_id'] ?? Kategori::query()->value('id');
        if (!$kategoriId) {
            $kategoriId = Kategori::query()->create(['nama_kategori' => 'Umum'])->id;
        }

        $payload = [
            'admin_id' => (int) $admin->id,
            'user_id' => (int) Auth::id(),
            'kategori_id' => (int) $kategoriId,
            'nama_barang' => $validated['nama_barang'],
            'warna_barang' => $validated['warna_barang'] ?? null,
            'merek_barang' => $validated['merek_barang'] ?? null,
            'nomor_seri' => $validated['nomor_seri'] ?? null,
            'deskripsi' => $validated['deskripsi'],
            'ciri_khusus' => $validated['ciri_khusus'] ?? null,
            'nama_penemu' => $validated['nama_penemu'] ?? (Auth::user()?->nama ?? Auth::user()?->name ?? null),
            'kontak_penemu' => $validated['kontak_penemu'],
            'lokasi_ditemukan' => $validated['lokasi_ditemukan'],
            'detail_lokasi_ditemukan' => $validated['detail_lokasi_ditemukan'] ?? null,
            'tanggal_ditemukan' => $validated['tanggal_ditemukan'],
            'waktu_ditemukan' => $validated['waktu_ditemukan'] ?? null,
            'status_barang' => 'tersedia',
        ];

        $photo = $request->file('foto_barang');
        if ($photo) {
            $payload['foto_barang'] = $photo->store('barang-temuan/' . now()->format('Y/m'), 'public');
        }

        Barang::create($payload);

        return back()->with('status', 'Laporan barang temuan berhasil dikirim.');
    }

    public function storeClaim(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return back()->with('error', 'Anda harus login sebelum mengajukan klaim.');
        }

        $claimWithoutReport = $request->boolean('claim_without_report');
        $validated = $request->validate([
            'barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'claim_without_report' => ['nullable', 'boolean'],
            'laporan_hilang_id' => [$claimWithoutReport ? 'nullable' : 'required', 'integer', 'exists:laporan_barang_hilangs,id'],
            'kontak_pelapor' => ['required', 'string', 'max:50'],
            'bukti_kepemilikan' => ['required', 'string', 'max:2000'],
            'bukti_foto' => ['required', 'array', 'min:1', 'max:3'],
            'bukti_foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'catatan' => ['nullable', 'string'],
            'persetujuan_klaim' => ['accepted'],
            'lost_nama_barang' => [$claimWithoutReport ? 'required' : 'nullable', 'string', 'max:255'],
            'lost_kategori_barang' => ['nullable', 'string', 'max:100'],
            'lost_warna_barang' => ['nullable', 'string', 'max:100'],
            'lost_merek_barang' => ['nullable', 'string', 'max:120'],
            'lost_nomor_seri' => ['nullable', 'string', 'max:150'],
            'lost_lokasi_hilang' => [$claimWithoutReport ? 'required' : 'nullable', 'string', 'max:255'],
            'lost_detail_lokasi_hilang' => ['nullable', 'string', 'max:2000'],
            'lost_tanggal_hilang' => [$claimWithoutReport ? 'required' : 'nullable', 'date'],
            'lost_keterangan' => [$claimWithoutReport ? 'required' : 'nullable', 'string'],
            'lost_ciri_khusus' => ['nullable', 'string', 'max:2000'],
        ]);

        $barang = Barang::query()->select('id', 'admin_id', 'status_barang')->find($validated['barang_id']);
        if (!$barang) {
            return back()->with('error', 'Barang temuan tidak ditemukan.');
        }

        $hasDuplicateClaim = Klaim::query()
            ->where('user_id', (int) Auth::id())
            ->where('barang_id', (int) $barang->id)
            ->whereIn('status_klaim', ['pending', 'disetujui'])
            ->exists();
        if ($hasDuplicateClaim) {
            return back()->with('error', 'Anda sudah pernah mengajukan klaim aktif untuk barang ini.');
        }

        if ($claimWithoutReport) {
            $laporanPayload = [
                'user_id' => (int) Auth::id(),
                'nama_barang' => $validated['lost_nama_barang'],
                'kategori_barang' => $validated['lost_kategori_barang'] ?? null,
                'warna_barang' => $validated['lost_warna_barang'] ?? null,
                'merek_barang' => $validated['lost_merek_barang'] ?? null,
                'nomor_seri' => $validated['lost_nomor_seri'] ?? null,
                'lokasi_hilang' => $validated['lost_lokasi_hilang'],
                'detail_lokasi_hilang' => $validated['lost_detail_lokasi_hilang'] ?? null,
                'tanggal_hilang' => $validated['lost_tanggal_hilang'],
                'keterangan' => $validated['lost_keterangan'],
                'ciri_khusus' => $validated['lost_ciri_khusus'] ?? null,
                'kontak_pelapor' => $validated['kontak_pelapor'],
                'bukti_kepemilikan' => $validated['bukti_kepemilikan'],
            ];

            if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
                $laporanPayload['sumber_laporan'] = 'lapor_hilang';
            }

            $laporan = LaporanBarangHilang::create($laporanPayload);
        } else {
            $laporan = LaporanBarangHilang::query()
                ->where('id', (int) $validated['laporan_hilang_id'])
                ->where('user_id', (int) Auth::id())
                ->first();

            if (!$laporan) {
                return back()->with('error', 'Pilih laporan barang hilang milik Anda yang valid sebelum mengajukan klaim.');
            }
        }

        $hasBlockingClaimForReport = Klaim::query()
            ->where('laporan_hilang_id', (int) $laporan->id)
            ->whereIn('status_klaim', ['pending', 'disetujui'])
            ->exists();
        if ($hasBlockingClaimForReport) {
            return back()->with('error', 'Laporan ini masih punya klaim aktif. Tunggu proses klaim sebelumnya selesai.');
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

        Klaim::create([
            'laporan_hilang_id' => (int) $laporan->id,
            'barang_id' => (int) $barang->id,
            'user_id' => (int) Auth::id(),
            'admin_id' => (int) $barang->admin_id,
            'status_klaim' => 'pending',
            'catatan' => $validated['catatan'] ?? null,
            'bukti_foto' => $buktiFotoPaths,
        ]);

        if ($barang->status_barang === 'tersedia') {
            $barang->update(['status_barang' => 'dalam_proses_klaim']);
        }

        return back()->with('status', 'Pengajuan klaim berhasil dikirim untuk verifikasi admin.');
    }
}
