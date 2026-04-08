<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\LaporanBarangHilang;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $lostItems = [];
        $lostTotalCount = 0;
        if (Schema::hasTable('laporan_barang_hilangs')) {
            $lostQuery = LaporanBarangHilang::query();
            $lostTotalCount = (clone $lostQuery)->count();

            $lostItems = $lostQuery
                ->latest('tanggal_hilang')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => 'UMUM',
                        'name' => $item->nama_barang,
                        'location' => $item->lokasi_hilang,
                        'date' => $item->tanggal_hilang ? date('m/d/Y', strtotime((string) $item->tanggal_hilang)) : '',
                    ];
                })
                ->values()
                ->all();
        }

        $foundItems = [];
        $foundTotalCount = 0;
        if (Schema::hasTable('barangs')) {
            $foundQuery = Barang::query()->with('kategori:id,nama_kategori');
            $foundTotalCount = (clone $foundQuery)->count();

            $foundItems = $foundQuery
                ->latest('tanggal_ditemukan')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => strtoupper($item->kategori->nama_kategori ?? 'UMUM'),
                        'name' => $item->nama_barang,
                        'location' => $item->lokasi_ditemukan,
                        'date' => $item->tanggal_ditemukan ? date('m/d/Y', strtotime((string) $item->tanggal_ditemukan)) : '',
                    ];
                })
                ->values()
                ->all();
        }

        $categories = ['Semua Kategori'];
        if (Schema::hasTable('kategoris')) {
            $categories = array_merge(
                ['Semua Kategori'],
                Kategori::query()
                    ->orderBy('nama_kategori')
                    ->pluck('nama_kategori')
                    ->map(fn ($name) => ucwords(strtolower($name)))
                    ->values()
                    ->all()
            );
        }

        $regions = ['Seluruh Wilayah'];
        $mapRegions = [];
        if (Schema::hasTable('wilayahs')) {
            $wilayahs = Wilayah::query()
                ->orderBy('nama_wilayah')
                ->get(['nama_wilayah', 'lat', 'lng']);

            $regions = array_merge(['Seluruh Wilayah'], $wilayahs->pluck('nama_wilayah')->all());

            $allLocations = collect(array_merge(
                array_column($lostItems, 'location'),
                array_column($foundItems, 'location')
            ))->map(fn ($loc) => Str::lower((string) $loc));

            $mapRegions = $wilayahs->map(function ($wilayah) use ($allLocations) {
                $key = Str::lower(str_replace('kecamatan', '', $wilayah->nama_wilayah));
                $activePoints = $allLocations->filter(function ($loc) use ($key) {
                    return str_contains($loc, trim($key));
                })->count();

                return [
                    'name' => $wilayah->nama_wilayah,
                    'slug' => Str::slug($wilayah->nama_wilayah),
                    'lat' => $wilayah->lat ? (float) $wilayah->lat : null,
                    'lng' => $wilayah->lng ? (float) $wilayah->lng : null,
                    'active_points' => $activePoints,
                ];
            })->values()->all();
        }

        $userName = Auth::user()->nama ?? Auth::user()->name ?? 'Pengguna';
        $userLocation = Auth::user()->location ?? 'Lokasi Anda';

        return view('home', compact(
            'lostItems',
            'foundItems',
            'lostTotalCount',
            'foundTotalCount',
            'categories',
            'regions',
            'mapRegions',
            'userName',
            'userLocation'
        ));
    }
}
