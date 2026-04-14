@extends('admin.layouts.app')

@php
    $pageTitle = 'Edit Data Barang Hilang - SiNemu';
    $activeMenu = 'lost-items';
    $hideSearch = true;
    $hideSidebar = true;
    $topbarBackUrl = route('admin.lost-items');
    $topbarBackLabel = 'Kembali ke Daftar Barang Hilang';
    $fotoPath = trim((string) ($laporanBarangHilang->foto_barang ?? ''), '/');
    [$folder, $subPath] = array_pad(explode('/', $fotoPath, 2), 2, '');
    $fotoUrl = !empty($fotoPath) && in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== ''
        ? route('media.image', ['folder' => $folder, 'path' => $subPath], false)
        : route('media.image', ['folder' => 'barang-temuan', 'path' => 'hp.webp'], false);
    $createdAtLabel = !empty($laporanBarangHilang->created_at)
        ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->created_at)->format('d M Y, H:i')
        : '-';
    $updatedAtLabel = !empty($laporanBarangHilang->updated_at)
        ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->updated_at)->format('d M Y, H:i')
        : '-';
@endphp

@section('page-content')
    <section class="edit-report-page">
        @if(session('status'))
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup success" data-autoclose="3200" style="--autoclose-ms: 3200ms;" role="status" aria-live="polite">
                <span class="feedback-alert-icon" aria-hidden="true"><iconify-icon icon="mdi:check-circle"></iconify-icon></span>
                <div class="feedback-alert-body">
                    <strong>Berhasil</strong>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" class="feedback-alert-close" data-alert-close aria-label="Tutup notifikasi">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <span class="feedback-alert-progress" aria-hidden="true"></span>
            </div>
        @endif
        @if(session('error'))
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup error" data-autoclose="3600" style="--autoclose-ms: 3600ms;" role="alert" aria-live="assertive">
                <span class="feedback-alert-icon" aria-hidden="true"><iconify-icon icon="mdi:alert-circle"></iconify-icon></span>
                <div class="feedback-alert-body">
                    <strong>Gagal</strong>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" class="feedback-alert-close" data-alert-close aria-label="Tutup notifikasi">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <span class="feedback-alert-progress" aria-hidden="true"></span>
            </div>
        @endif
        @if($errors->any())
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup error" data-autoclose="3600" style="--autoclose-ms: 3600ms;" role="alert" aria-live="assertive">
                <span class="feedback-alert-icon" aria-hidden="true"><iconify-icon icon="mdi:alert-circle"></iconify-icon></span>
                <div class="feedback-alert-body">
                    <strong>Gagal</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
                <button type="button" class="feedback-alert-close" data-alert-close aria-label="Tutup notifikasi">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <span class="feedback-alert-progress" aria-hidden="true"></span>
            </div>
        @endif

        <div class="edit-report-top">
            <div class="edit-report-header">
                <p class="edit-report-breadcrumb">
                    <a href="{{ route('admin.lost-items') }}">Daftar Barang Hilang</a>
                    <span>/</span>
                    <strong>Edit Data</strong>
                </p>
                <h1>Edit Data Barang Hilang</h1>
                <p class="edit-report-subtitle">Perbarui data laporan barang hilang lalu simpan perubahan.</p>
                <div class="edit-report-meta">
                    <span>Laporan #{{ $laporanBarangHilang->id }}</span>
                    <span>Dibuat {{ $createdAtLabel }} WIB</span>
                    <span>Diperbarui {{ $updatedAtLabel }} WIB</span>
                </div>
            </div>

            <aside class="edit-report-summary">
                <span class="edit-summary-label">Foto Saat Ini</span>
                <div class="edit-summary-photo-wrap">
                    <img id="editCurrentPhotoPreview" src="{{ $fotoUrl }}" alt="{{ $laporanBarangHilang->nama_barang }}">
                </div>
                <div class="edit-summary-grid">
                    <div class="edit-summary-item">
                        <small>Tanggal Hilang</small>
                        <strong>{{ !empty($laporanBarangHilang->tanggal_hilang) ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->tanggal_hilang)->format('d M Y') : '-' }}</strong>
                    </div>
                    <div class="edit-summary-item">
                        <small>Lokasi</small>
                        <strong>{{ $laporanBarangHilang->lokasi_hilang ?: '-' }}</strong>
                    </div>
                </div>
            </aside>
        </div>

        <section class="report-card edit-report-card">
            <header>
                <h2>Form Edit Data Barang Hilang</h2>
                <p>Laporan #{{ $laporanBarangHilang->id }}</p>
            </header>

            <form method="POST" action="{{ route('admin.lost-items.update', $laporanBarangHilang->id) }}" enctype="multipart/form-data" class="edit-report-form">
                @csrf
                @method('PATCH')

                <div class="edit-form-section">
                    <h3>Informasi Laporan</h3>
                    <div class="edit-form-grid edit-form-grid-two">
                        <div class="edit-form-col-full">
                            <label class="edit-form-label" for="nama_barang">Nama Barang</label>
                            <input class="form-input edit-form-input" id="nama_barang" name="nama_barang" type="text" required maxlength="255" value="{{ old('nama_barang', $laporanBarangHilang->nama_barang) }}">
                        </div>

                        <div>
                            <label class="edit-form-label" for="tanggal_hilang">Tanggal Hilang</label>
                            <input class="form-input edit-form-input" id="tanggal_hilang" name="tanggal_hilang" type="date" required value="{{ old('tanggal_hilang', !empty($laporanBarangHilang->tanggal_hilang) ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->tanggal_hilang)->format('Y-m-d') : '') }}">
                        </div>

                        <div>
                            <label class="edit-form-label" for="lokasi_hilang">Lokasi Hilang</label>
                            <input class="form-input edit-form-input" id="lokasi_hilang" name="lokasi_hilang" type="text" required maxlength="255" value="{{ old('lokasi_hilang', $laporanBarangHilang->lokasi_hilang) }}">
                        </div>

                        <div class="edit-form-col-full">
                            <label class="edit-form-label" for="keterangan">Keterangan</label>
                            <textarea class="form-input edit-form-input edit-form-textarea" id="keterangan" name="keterangan" maxlength="2000">{{ old('keterangan', $laporanBarangHilang->keterangan) }}</textarea>
                            <small class="edit-form-help">Tambahkan ciri khas barang (warna, merek, nomor seri, atau detail pembeda).</small>
                            <small class="edit-form-counter" id="keterangan_counter" aria-live="polite">0/2000 karakter</small>
                        </div>
                    </div>
                </div>

                <div class="edit-form-section">
                    <h3>Media</h3>
                    <div class="edit-form-grid">
                        <label class="edit-form-label" for="foto_barang">Foto Barang (Opsional)</label>
                        <input class="form-input edit-form-input" id="foto_barang" name="foto_barang" type="file" accept=".jpg,.jpeg,.png,.webp">
                        <small class="edit-form-help">Biarkan kosong jika tidak ingin mengganti foto.</small>
                        <small class="edit-form-file-name" id="foto_barang_filename">Belum ada file dipilih.</small>
                    </div>
                </div>

                <div class="edit-report-form-actions">
                    <a href="{{ route('admin.lost-items.show', $laporanBarangHilang->id) }}" class="filter-btn">Batal</a>
                    <button type="submit" class="filter-btn primary">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const photoInput = document.getElementById('foto_barang');
            const photoPreview = document.getElementById('editCurrentPhotoPreview');
            const photoFileName = document.getElementById('foto_barang_filename');
            const keterangan = document.getElementById('keterangan');
            const keteranganCounter = document.getElementById('keterangan_counter');
            const maxKeterangan = Number(keterangan?.getAttribute('maxlength') || 2000);

            function syncKeteranganCounter() {
                if (!keterangan || !keteranganCounter) return;
                const count = keterangan.value.length;
                keteranganCounter.textContent = `${count}/${maxKeterangan} karakter`;
            }

            if (keterangan) {
                syncKeteranganCounter();
                keterangan.addEventListener('input', syncKeteranganCounter);
            }

            if (!photoInput || !photoPreview) return;

            let objectUrl = null;

            photoInput.addEventListener('change', function () {
                const file = photoInput.files?.[0];
                if (!file) {
                    if (photoFileName) photoFileName.textContent = 'Belum ada file dipilih.';
                    return;
                }

                if (photoFileName) {
                    photoFileName.textContent = `File dipilih: ${file.name}`;
                }

                if (!file.type.startsWith('image/')) return;

                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                }

                objectUrl = URL.createObjectURL(file);
                photoPreview.src = objectUrl;
            });
        });
    </script>
@endsection
