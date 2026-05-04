@extends('admin.layouts.app')

@php
    $pageTitle = 'Edit Barang Wilayah - Admin';
    $activeMenu = 'found-items';
    $topbarBackUrl = route('admin.barang-wilayah.index');
    $topbarBackLabel = 'Kembali ke Barang Wilayah';
@endphp

@section('page-content')
    <div class="container-fluid py-3 py-md-4">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 760px;">
            <div class="card-header bg-white">
                <h1 class="h5 fw-bold mb-1">Edit Barang Wilayah</h1>
                <p class="text-secondary small mb-0">Barang ini hanya bisa diedit oleh admin dengan wilayah yang sama.</p>
            </div>
            <form method="POST" action="{{ route('admin.barang-wilayah.update', $barang) }}" class="card-body">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input id="nama_barang" name="nama_barang" type="text" class="form-control" value="{{ old('nama_barang', $barang->nama_barang) }}" required>
                </div>

                <div class="mb-3">
                    <label for="lokasi_ditemukan" class="form-label">Lokasi Ditemukan</label>
                    <input id="lokasi_ditemukan" name="lokasi_ditemukan" type="text" class="form-control" value="{{ old('lokasi_ditemukan', $barang->lokasi_ditemukan) }}" required>
                </div>

                <div class="mb-3">
                    <label for="status_barang" class="form-label">Status Barang</label>
                    <input id="status_barang" name="status_barang" type="text" class="form-control" value="{{ old('status_barang', $barang->status_barang) }}">
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4">{{ old('deskripsi', $barang->deskripsi) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.barang-wilayah.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
