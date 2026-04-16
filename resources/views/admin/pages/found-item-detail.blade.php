@extends('admin.layouts.app')

@php
    /** BAGIAN: Meta Halaman */
    $pageTitle = 'Detail Barang Temuan - SiNemu';
    $activeMenu = 'found-items';
    $searchAction = route('admin.found-items');
    $searchPlaceholder = 'Cari laporan atau barang';
    $hideSearch = true;
    $hideSidebar = true;
    $topbarBackUrl = route('admin.found-items');
    $topbarBackLabel = 'Kembali ke Daftar Barang Temuan';

    $fotoPath = trim((string) ($barang->foto_barang ?? ''), '/');
    [$folder, $subPath] = array_pad(explode('/', $fotoPath, 2), 2, '');
    $fotoUrl = !empty($fotoPath) && in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== ''
        ? route('media.image', ['folder' => $folder, 'path' => $subPath], false)
        : route('media.image', ['folder' => 'barang-temuan', 'path' => 'hp.webp'], false);

    $statusMap = [
        'tersedia' => ['TERSEDIA', 'status-dalam_peninjauan'],
        'dalam_proses_klaim' => ['DALAM PROSES KLAIM', 'status-diproses'],
        'sudah_diklaim' => ['SUDAH DIKLAIM', 'status-selesai'],
        'sudah_dikembalikan' => ['SELESAI', 'status-selesai'],
    ];
    $statusOptionLabels = [
        'tersedia' => 'Tersedia',
        'dalam_proses_klaim' => 'Dalam Proses Klaim',
        'sudah_diklaim' => 'Sudah Diklaim',
        'sudah_dikembalikan' => 'Sudah Dikembalikan',
    ];
    [$statusLabel, $statusClass] = $statusMap[$barang->status_barang] ?? ['UNKNOWN', 'status-diproses'];
    $petugasName = $barang->admin?->nama ?? 'Admin';
    $petugasEmail = $barang->admin?->email ?? 'Email tidak tersedia';
    $hasPetugasEmail = filter_var($petugasEmail, FILTER_VALIDATE_EMAIL) !== false;
    $emailContactHref = $hasPetugasEmail
        ? 'mailto:' . $petugasEmail
        : '#';
    $contactSubject = rawurlencode('Tindak lanjut laporan barang temuan #' . $barang->id);
    $contactBody = rawurlencode('Halo ' . $petugasName . ', kami ingin menindaklanjuti laporan barang temuan ini.');
    $hubungiHref = $hasPetugasEmail
        ? ('mailto:' . $petugasEmail . '?subject=' . $contactSubject . '&body=' . $contactBody)
        : '#';
    $createdAtLabel = !empty($barang->created_at)
        ? \Illuminate\Support\Carbon::parse($barang->created_at)->format('d M Y, H:i')
        : '-';
    $updatedAtLabel = !empty($barang->updated_at)
        ? \Illuminate\Support\Carbon::parse($barang->updated_at)->format('d M Y, H:i')
        : '-';
    $initials = collect(explode(' ', trim($petugasName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $statusHistories = $barang->statusHistories->take(8);
    $tanggalDitemukanLabel = !empty($barang->tanggal_ditemukan)
        ? \Illuminate\Support\Carbon::parse($barang->tanggal_ditemukan)->format('d M Y')
        : '-';
    $waktuDitemukanRaw = (string) ($barang->waktu_ditemukan ?? '');
    $waktuDitemukanLabel = $waktuDitemukanRaw !== ''
        ? (date('H:i', strtotime($waktuDitemukanRaw)) ?: $waktuDitemukanRaw)
        : '-';
    $penemuName = $barang->nama_penemu ?: $petugasName;
    $penemuContact = $barang->kontak_penemu ?: '-';
    $penemuInitials = collect(explode(' ', trim((string) $penemuName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

@section('page-content')
    <section class="found-detail-page">
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

        <div class="found-detail-header">
            <div>
                <p class="found-detail-breadcrumb">
                    <a href="{{ route('admin.found-items') }}">Daftar Barang Temuan</a>
                    <span>/</span>
                    <strong>Detail Barang</strong>
                </p>
                <h1>Detail Laporan Barang Temuan</h1>
                <div class="found-detail-header-meta">
                    <span>Laporan #{{ $barang->id }}</span>
                    <span>Dibuat {{ $createdAtLabel }} WIB</span>
                    <span>Diperbarui {{ $updatedAtLabel }} WIB</span>
                </div>
            </div>
        </div>

        <div class="found-detail-grid">
            <article class="report-card found-detail-main">
                <div class="found-detail-main-content">
                    <div class="found-detail-image-wrap">
                        <span class="found-detail-image-label">Foto Barang</span>
                        <img
                            src="{{ $fotoUrl }}"
                            alt="{{ $barang->nama_barang }}"
                            class="found-detail-image"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>

                    <div class="found-detail-body">
                        <h2>{{ strtoupper($barang->nama_barang) }}</h2>
                        <p>{{ $barang->deskripsi ?: 'Deskripsi barang belum ditambahkan pada laporan ini.' }}</p>

                        <div class="found-detail-meta">
                            <div>
                                <span>Kategori</span>
                                <strong>{{ $barang->kategori?->nama_kategori ?? 'Tanpa Kategori' }}</strong>
                            </div>
                            <div>
                                <span>Tanggal Ditemukan</span>
                                <strong>{{ $tanggalDitemukanLabel }}</strong>
                            </div>
                            <div>
                                <span>Lokasi Ditemukan</span>
                                <strong>{{ $barang->lokasi_ditemukan ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>ID Laporan</span>
                                <strong>#{{ $barang->id }}</strong>
                            </div>
                        </div>

                        <div class="found-detail-meta">
                            <div>
                                <span>Warna</span>
                                <strong>{{ $barang->warna_barang ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Merek</span>
                                <strong>{{ $barang->merek_barang ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Nomor Seri / Kode</span>
                                <strong>{{ $barang->nomor_seri ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Jam Ditemukan</span>
                                <strong>{{ $waktuDitemukanLabel !== '-' ? $waktuDitemukanLabel.' WIB' : '-' }}</strong>
                            </div>
                        </div>

                        @if(!empty($barang->detail_lokasi_ditemukan) || !empty($barang->ciri_khusus))
                            <div class="found-detail-meta">
                                <div>
                                    <span>Detail Lokasi Ditemukan</span>
                                    <strong>{{ $barang->detail_lokasi_ditemukan ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>Ciri Unik</span>
                                    <strong>{{ $barang->ciri_khusus ?: '-' }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            <div class="found-detail-side">
                <article class="report-card found-detail-panel found-panel-status">
                    <header>
                        <h2>Status Saat Ini</h2>
                    </header>
                    <div class="found-detail-panel-body">
                        <span class="status-chip {{ $statusClass }}">{{ $statusLabel }}</span>

                        <form method="POST" action="{{ route('admin.found-items.update-status', $barang->id) }}" class="status-edit-form" id="status-update-form" data-confirm-delete data-confirm-title="Konfirmasi Perbarui Status" data-confirm-submit-label="Perbarui" data-confirm-submit-variant="primary" data-confirm-message="Perbarui status barang temuan ini? Pastikan data sudah sesuai sebelum menyimpan.">
                            @csrf
                            @method('PATCH')
                            <label for="status_barang" class="status-form-label">Status Baru</label>
                            <select name="status_barang" id="status_barang" class="form-input status-form-input">
                                @foreach($statusOptionLabels as $statusValue => $statusText)
                                    <option value="{{ $statusValue }}" @selected(old('status_barang', $barang->status_barang) === $statusValue)>{{ $statusText }}</option>
                                @endforeach
                            </select>

                            <label for="catatan_status" class="status-form-label">Catatan (Opsional)</label>
                            <textarea name="catatan_status" id="catatan_status" class="form-input form-textarea-sm status-form-input" placeholder="Contoh: Barang sudah diserahkan ke pemilik.">{{ old('catatan_status') }}</textarea>
                        </form>
                    </div>
                </article>

                <article class="report-card found-detail-panel found-panel-reporter">
                    <header><h2>Informasi Penemu</h2></header>
                    <div class="found-detail-panel-body">
                        <div class="found-person-row">
                            <span class="found-person-avatar">{{ $penemuInitials ?: ($initials ?: 'US') }}</span>
                            <div>
                                <p><strong>{{ $penemuName }}</strong></p>
                                <small>Pelapor / Penemu</small>
                            </div>
                        </div>
                        <div class="found-contact-actions">
                            <a href="{{ $hubungiHref }}" class="filter-btn {{ $hasPetugasEmail ? '' : 'is-disabled' }}">Hubungi</a>
                            <a href="{{ $emailContactHref }}" class="filter-btn {{ $hasPetugasEmail ? '' : 'is-disabled' }}">Email</a>
                        </div>
                        <p>{{ $penemuContact !== '-' ? ('WA: '.$penemuContact) : $petugasEmail }}</p>
                    </div>
                </article>

                <article class="report-card found-detail-panel found-panel-location">
                    <header><h2>Lokasi &amp; Waktu Penyimpanan</h2></header>
                    <div class="found-detail-panel-body">
                        <div class="found-info-item">
                            <span class="found-info-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7a1 1 0 0 1 1 1v4.1l2.2 1.47a1 1 0 1 1-1.1 1.66l-2.65-1.76A1 1 0 0 1 11 13V8a1 1 0 0 1 1-1zM12 3a9 9 0 1 1 0 18a9 9 0 0 1 0-18zm0 2a7 7 0 1 0 0 14a7 7 0 0 0 0-14z" fill="currentColor"/></svg>
                            </span>
                            <div>
                                <small>Ditemukan</small>
                                <p><strong>{{ $tanggalDitemukanLabel }}{{ $waktuDitemukanLabel !== '-' ? ', '.$waktuDitemukanLabel : '' }} WIB</strong></p>
                            </div>
                        </div>
                        <div class="found-info-item">
                            <span class="found-info-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4a1 1 0 0 1 1 1v1h8V5a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v3H3V8a2 2 0 0 1 2-2h1V5a1 1 0 0 1 1-1zm14 9v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6h18zm-8 2H7a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2z" fill="currentColor"/></svg>
                            </span>
                            <div>
                                <small>Disimpan di</small>
                                <p><strong>{{ $barang->lokasi_pengambilan ?: $barang->lokasi_ditemukan ?: '-' }}</strong></p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="report-card found-detail-panel found-panel-activity">
                    <header><h2>Riwayat Aktivitas</h2></header>
                    <div class="found-detail-panel-body">
                        @forelse($statusHistories as $history)
                            <div class="activity-item">
                                <p><strong>Status Diperbarui</strong></p>
                                <small>
                                    {{ ($statusOptionLabels[$history->status_lama] ?? strtoupper(str_replace('_', ' ', (string) $history->status_lama))) ?: '-' }}
                                    ke
                                    {{ $statusOptionLabels[$history->status_baru] ?? strtoupper(str_replace('_', ' ', (string) $history->status_baru)) }}
                                    - {{ $history->admin?->nama ?? 'Admin' }} - {{ \Illuminate\Support\Carbon::parse($history->created_at)->format('d M Y, H:i') }} WIB
                                </small>
                                @if(!empty($history->catatan))
                                    <small>Catatan: {{ $history->catatan }}</small>
                                @endif
                            </div>
                        @empty
                            <div class="activity-item">
                                <p><strong>Laporan Dibuat</strong></p>
                                <small>{{ !empty($barang->created_at) ? \Illuminate\Support\Carbon::parse($barang->created_at)->format('d M Y, H:i') : '-' }} WIB</small>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>
        </div>

        <div class="found-detail-bottom-actions">
            <button type="submit" form="status-update-form" class="filter-btn found-action-btn found-action-btn-primary" disabled>Perbarui Status</button>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('found-detail-page-mode');

            const form = document.getElementById('status-update-form');
            if (!form) return;

            const submitButton = document.querySelector('button[form="status-update-form"][type="submit"]');
            if (!submitButton) return;

            const statusInput = form.querySelector('#status_barang');
            const noteInput = form.querySelector('#catatan_status');

            if (!statusInput || !noteInput || statusInput.disabled || noteInput.disabled) return;

            const initialStatus = statusInput.value;
            const initialNote = noteInput.value;

            const syncSubmitState = function () {
                const hasChanged = statusInput.value !== initialStatus || noteInput.value !== initialNote;
                submitButton.disabled = !hasChanged;
            };

            statusInput.addEventListener('change', syncSubmitState);
            noteInput.addEventListener('input', syncSubmitState);

            form.addEventListener('submit', function () {
                submitButton.disabled = true;
                submitButton.textContent = 'Menyimpan...';
            });

            syncSubmitState();
        });
    </script>
@endsection
