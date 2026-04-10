<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FoundItemController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $query = Barang::query()
            ->with(['kategori', 'admin:id,nama'])
            ->orderByDesc('created_at');

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
                $query->orderBy('tanggal_ditemukan');
                break;
            case 'nama_asc':
                $query->orderBy('nama_barang');
                break;
            case 'nama_desc':
                $query->orderByDesc('nama_barang');
                break;
            default:
                $query->orderByDesc('tanggal_ditemukan');
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
}
