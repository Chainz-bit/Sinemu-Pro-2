<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Klaim;
use App\Models\LaporanBarangHilang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserItemActionController extends Controller
{
    public function storeLostReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'lokasi_hilang' => ['required', 'string', 'max:255'],
            'tanggal_hilang' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $payload = [
            'user_id' => (int) Auth::id(),
            'nama_barang' => $validated['nama_barang'],
            'lokasi_hilang' => $validated['lokasi_hilang'],
            'tanggal_hilang' => $validated['tanggal_hilang'],
            'keterangan' => $validated['keterangan'] ?? null,
        ];

        if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
            $payload['sumber_laporan'] = 'lapor_hilang';
        }

        LaporanBarangHilang::create($payload);

        return back()->with('status', 'Laporan barang hilang berhasil dikirim.');
    }

    public function storeFoundReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategoris,id'],
            'deskripsi' => ['required', 'string'],
            'lokasi_ditemukan' => ['required', 'string', 'max:255'],
            'tanggal_ditemukan' => ['required', 'date'],
        ]);

        $admin = Admin::query()->select('id')->orderBy('id')->first();
        if (!$admin) {
            return back()->with('error', 'Belum ada admin aktif untuk menerima laporan temuan.');
        }

        $kategoriId = $validated['kategori_id'] ?? Kategori::query()->value('id');
        if (!$kategoriId) {
            $kategoriId = Kategori::query()->create(['nama_kategori' => 'Umum'])->id;
        }

        Barang::create([
            'admin_id' => (int) $admin->id,
            'kategori_id' => (int) $kategoriId,
            'nama_barang' => $validated['nama_barang'],
            'deskripsi' => $validated['deskripsi'],
            'lokasi_ditemukan' => $validated['lokasi_ditemukan'],
            'tanggal_ditemukan' => $validated['tanggal_ditemukan'],
            'status_barang' => 'tersedia',
        ]);

        return back()->with('status', 'Laporan barang temuan berhasil dikirim.');
    }

    public function storeClaim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barang_id' => ['required', 'integer', 'exists:barangs,id'],
            'nama_barang_hilang' => ['required', 'string', 'max:255'],
            'lokasi_hilang' => ['required', 'string', 'max:255'],
            'tanggal_hilang' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $barang = Barang::query()->select('id', 'admin_id', 'status_barang')->find($validated['barang_id']);
        if (!$barang) {
            return back()->with('error', 'Barang temuan tidak ditemukan.');
        }

        $laporan = LaporanBarangHilang::query()
            ->where('user_id', (int) Auth::id())
            ->where('nama_barang', $validated['nama_barang_hilang'])
            ->where('lokasi_hilang', $validated['lokasi_hilang'])
            ->whereDate('tanggal_hilang', $validated['tanggal_hilang'])
            ->first();

        if (!$laporan) {
            $payload = [
                'user_id' => (int) Auth::id(),
                'nama_barang' => $validated['nama_barang_hilang'],
                'lokasi_hilang' => $validated['lokasi_hilang'],
                'tanggal_hilang' => $validated['tanggal_hilang'],
                'keterangan' => $validated['keterangan'] ?? null,
            ];

            if (Schema::hasColumn('laporan_barang_hilangs', 'sumber_laporan')) {
                $payload['sumber_laporan'] = 'klaim';
            }

            $laporan = LaporanBarangHilang::create($payload);
        }

        Klaim::create([
            'laporan_hilang_id' => (int) $laporan->id,
            'barang_id' => (int) $barang->id,
            'user_id' => (int) Auth::id(),
            'admin_id' => (int) $barang->admin_id,
            'status_klaim' => 'pending',
            'catatan' => $validated['catatan'] ?? null,
        ]);

        if ($barang->status_barang === 'tersedia') {
            $barang->update(['status_barang' => 'dalam_proses_klaim']);
        }

        return back()->with('status', 'Pengajuan klaim berhasil dikirim untuk verifikasi admin.');
    }
}
