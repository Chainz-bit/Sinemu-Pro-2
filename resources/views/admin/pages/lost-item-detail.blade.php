@extends('admin.layouts.app')

@php
    $pageTitle = 'Detail Barang Hilang - SiNemu';
    $activeMenu = 'lost-items';
    $searchAction = route('admin.lost-items');
    $searchPlaceholder = 'Cari laporan atau barang';
    $hideSearch = true;
    $hideSidebar = true;
    $topbarBackUrl = route('admin.lost-items');
    $topbarBackLabel = 'Kembali ke Daftar Barang Hilang';

    $fotoPath = trim((string) ($laporanBarangHilang->foto_barang ?? ''), '/');
    [$folder, $subPath] = array_pad(explode('/', $fotoPath, 2), 2, '');
    $fotoUrl = !empty($fotoPath) && in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== ''
        ? route('media.image', ['folder' => $folder, 'path' => $subPath], false)
        : (str_contains(strtolower((string) $laporanBarangHilang->nama_barang), 'dompet')
            ? route('media.image', ['folder' => 'barang-hilang', 'path' => 'dompet.webp'], false)
            : route('media.image', ['folder' => 'barang-temuan', 'path' => 'hp.webp'], false));

    $statusMap = [
        null => ['BELUM DITEMUKAN', 'status-dalam_peninjauan'],
        'pending' => ['DALAM PENINJAUAN', 'status-diproses'],
        'disetujui' => ['DITEMUKAN', 'status-selesai'],
        'ditolak' => ['DITOLAK', 'status-ditolak'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$latestKlaim->status_klaim ?? null] ?? ['BELUM DITEMUKAN', 'status-dalam_peninjauan'];

    $pelaporName = $laporanBarangHilang->user?->nama ?? $laporanBarangHilang->user?->name ?? 'Pengguna';
    $statusOptionLabels = [
        'pending' => 'Dalam Peninjauan',
        'disetujui' => 'Ditemukan',
        'ditolak' => 'Ditolak',
    ];
    $statusValue = $latestKlaim->status_klaim ?? 'pending';
    $pelaporEmail = $laporanBarangHilang->user?->email ?? 'Email tidak tersedia';
    $hasPelaporEmail = filter_var($pelaporEmail, FILTER_VALIDATE_EMAIL) !== false;
    $emailContactHref = $hasPelaporEmail
        ? 'mailto:' . $pelaporEmail
        : '#';
    $contactSubject = rawurlencode('Tindak lanjut laporan barang hilang #' . $laporanBarangHilang->id);
    $contactBody = rawurlencode('Halo ' . $pelaporName . ', kami ingin menindaklanjuti laporan barang hilang Anda.');
    $hubungiHref = $hasPelaporEmail
        ? ('mailto:' . $pelaporEmail . '?subject=' . $contactSubject . '&body=' . $contactBody)
        : '#';
    $createdAtLabel = !empty($laporanBarangHilang->created_at)
        ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->created_at)->format('d M Y, H:i')
        : '-';
    $updatedAtLabel = !empty($laporanBarangHilang->updated_at)
        ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->updated_at)->format('d M Y, H:i')
        : '-';
    $tanggalHilangLabel = !empty($laporanBarangHilang->tanggal_hilang)
        ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->tanggal_hilang)->format('d M Y')
        : '-';
    $waktuHilangRaw = (string) ($laporanBarangHilang->waktu_hilang ?? '');
    $waktuHilangLabel = $waktuHilangRaw !== ''
        ? (date('H:i', strtotime($waktuHilangRaw)) ?: $waktuHilangRaw)
        : '-';
    $initials = collect(explode(' ', trim($pelaporName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

@section('page-content')
    <section class="lost-detail-page">
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

        <div class="lost-detail-header">
            <div>
                <p class="lost-detail-breadcrumb">
                    <a href="{{ route('admin.lost-items') }}">Daftar Barang Hilang</a>
                    <span>/</span>
                    <strong>Detail Barang</strong>
                </p>
                <h1>Detail Laporan Barang Hilang</h1>
                <div class="lost-detail-header-meta">
                    <span>Laporan #{{ $laporanBarangHilang->id }}</span>
                    <span>Dibuat {{ $createdAtLabel }} WIB</span>
                    <span>Diperbarui {{ $updatedAtLabel }} WIB</span>
                </div>
            </div>
        </div>

        <div class="lost-detail-grid">
            <article class="report-card lost-detail-main">
                <div class="lost-detail-main-content">
                    <div class="lost-detail-image-wrap">
                        <span class="lost-detail-image-label">Foto Barang</span>
                        <img
                            src="{{ $fotoUrl }}"
                            alt="{{ $laporanBarangHilang->nama_barang }}"
                            class="lost-detail-image"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>

                    <div class="lost-detail-body">
                        <h2>{{ strtoupper($laporanBarangHilang->nama_barang) }}</h2>
                        <p>{{ $laporanBarangHilang->keterangan ?: 'Tidak ada deskripsi tambahan.' }}</p>

                        <div class="lost-detail-meta">
                            <div>
                                <span>Kategori</span>
                                <strong>{{ $laporanBarangHilang->kategori_barang ?: 'Tidak Dikategorikan' }}</strong>
                            </div>
                            <div>
                                <span>Tanggal Hilang</span>
                                <strong>{{ $tanggalHilangLabel }}</strong>
                            </div>
                            <div>
                                <span>Lokasi Ditemukan Terakhir</span>
                                <strong>{{ $laporanBarangHilang->lokasi_hilang ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>ID Laporan</span>
                                <strong>#{{ $laporanBarangHilang->id }}</strong>
                            </div>
                        </div>

                        <div class="lost-detail-meta">
                            <div>
                                <span>Warna</span>
                                <strong>{{ $laporanBarangHilang->warna_barang ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Merek</span>
                                <strong>{{ $laporanBarangHilang->merek_barang ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Nomor Seri / Kode</span>
                                <strong>{{ $laporanBarangHilang->nomor_seri ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>No. WA Pelapor</span>
                                <strong>{{ $laporanBarangHilang->kontak_pelapor ?: '-' }}</strong>
                            </div>
                        </div>

                        @if(!empty($laporanBarangHilang->detail_lokasi_hilang) || !empty($laporanBarangHilang->ciri_khusus) || !empty($laporanBarangHilang->bukti_kepemilikan))
                            <div class="lost-detail-meta">
                                <div>
                                    <span>Detail Lokasi Hilang</span>
                                    <strong>{{ $laporanBarangHilang->detail_lokasi_hilang ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Ciri Unik</span>
                                    <strong>{{ $laporanBarangHilang->ciri_khusus ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Bukti Kepemilikan</span>
                                    <strong>{{ $laporanBarangHilang->bukti_kepemilikan ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Jam Hilang</span>
                                    <strong>{{ $waktuHilangLabel }} WIB</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            <div class="lost-detail-side">
                <article class="report-card lost-detail-panel lost-panel-status">
                    <header><h2>Status Saat Ini</h2></header>
                    <div class="lost-detail-panel-body">
                        <span class="status-chip {{ $statusClass }}">{{ $statusLabel }}</span>

                        <form method="POST" action="{{ route('admin.lost-items.update-status', $laporanBarangHilang->id) }}" id="lost-status-update-form" class="lost-status-edit-form">
                            @csrf
                            @method('PATCH')

                            <div class="lost-form-group">
                                <label class="lost-status-form-label" for="status_klaim">Status Baru</label>
                                <select id="status_klaim" name="status_klaim" class="form-input lost-status-form-input">
                                    @foreach($statusOptionLabels as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected(old('status_klaim', $statusValue) === $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lost-form-group">
                                <label class="lost-status-form-label" for="catatan">Catatan (Opsional)</label>
                                <textarea
                                    id="catatan"
                                    name="catatan"
                                    class="form-input form-textarea-sm lost-status-form-input"
                                    placeholder="{{ $latestKlaim?->catatan ?: 'Contoh: Bukti kepemilikan valid dan sudah diverifikasi admin.' }}"
                                >{{ old('catatan') }}</textarea>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="report-card lost-detail-panel lost-panel-reporter">
                    <header><h2>Informasi Pelapor</h2></header>
                    <div class="lost-detail-panel-body">
                        <div class="lost-person-row">
                            <span class="lost-person-avatar">{{ $initials ?: 'US' }}</span>
                            <div>
                                <p><strong>{{ $pelaporName }}</strong></p>
                                <small>Pelapor Barang Hilang</small>
                            </div>
                        </div>
                        <div class="lost-contact-actions">
                            <a href="{{ $hubungiHref }}" class="filter-btn {{ $hasPelaporEmail ? '' : 'is-disabled' }}">Hubungi</a>
                            <a href="{{ $emailContactHref }}" class="filter-btn {{ $hasPelaporEmail ? '' : 'is-disabled' }}">Email</a>
                        </div>
                        <p>{{ $pelaporEmail }}</p>
                    </div>
                </article>

                <article class="report-card lost-detail-panel lost-panel-location">
                    <header><h2>Lokasi &amp; Waktu Laporan</h2></header>
                    <div class="lost-detail-panel-body">
                        <div class="lost-info-item">
                            <span class="lost-info-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7a1 1 0 0 1 1 1v4.1l2.2 1.47a1 1 0 1 1-1.1 1.66l-2.65-1.76A1 1 0 0 1 11 13V8a1 1 0 0 1 1-1zM12 3a9 9 0 1 1 0 18a9 9 0 0 1 0-18zm0 2a7 7 0 1 0 0 14a7 7 0 0 0 0-14z" fill="currentColor"/></svg>
                            </span>
                            <div>
                                <small>Hilang Pada</small>
                                <p><strong>{{ $tanggalHilangLabel }} {{ $waktuHilangLabel !== '-' ? ', '.$waktuHilangLabel : '' }} WIB</strong></p>
                            </div>
                        </div>

                        <div class="lost-info-item">
                            <span class="lost-info-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4a1 1 0 0 1 1 1v1h8V5a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v3H3V8a2 2 0 0 1 2-2h1V5a1 1 0 0 1 1-1zm14 9v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6h18zm-8 2H7a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2z" fill="currentColor"/></svg>
                            </span>
                            <div>
                                <small>Lokasi Terakhir</small>
                                <p><strong>{{ $laporanBarangHilang->lokasi_hilang ?: '-' }}</strong></p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="report-card lost-detail-panel lost-panel-activity">
                    <header><h2>Riwayat Aktivitas</h2></header>
                    <div class="lost-detail-panel-body">
                        <div class="lost-activity-item">
                            <p><strong>Laporan Dibuat</strong></p>
                            <small>{{ !empty($laporanBarangHilang->created_at) ? \Illuminate\Support\Carbon::parse($laporanBarangHilang->created_at)->format('d M Y, H:i') : '-' }} WIB</small>
                        </div>
                        @if($latestKlaim)
                            <div class="lost-activity-item">
                                <p><strong>Status Klaim Terakhir</strong></p>
                                <small>{{ $statusLabel }} - {{ \Illuminate\Support\Carbon::parse($latestKlaim->created_at)->format('d M Y, H:i') }} WIB</small>
                            </div>
                        @endif
                    </div>
                </article>
            </div>
        </div>

        <div class="lost-detail-bottom-actions">
            <button type="submit" form="lost-status-update-form" class="filter-btn lost-action-btn lost-action-btn-primary">Perbarui Status</button>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('lost-detail-page-mode');

            const form = document.getElementById('lost-status-update-form');
            if (!form) return;

            const submitButton = document.querySelector('button[form="lost-status-update-form"][type="submit"]');
            if (!submitButton) return;

            const statusInput = form.querySelector('#status_klaim');
            const noteInput = form.querySelector('#catatan');

            if (!statusInput || !noteInput || statusInput.disabled || noteInput.disabled) return;

            const initialStatus = statusInput.value;
            const initialNote = noteInput.value;
            const initialText = submitButton.textContent.trim();

            const syncSubmitState = function () {
                const hasChanged = statusInput.value !== initialStatus || noteInput.value !== initialNote;
                submitButton.disabled = !hasChanged;
            };

            statusInput.addEventListener('change', syncSubmitState);
            noteInput.addEventListener('input', syncSubmitState);

            form.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.textContent = 'Menyimpan...';
                submitButton.dataset.originalText = initialText;
            });

            syncSubmitState();
        });
    </script>
@endsection
