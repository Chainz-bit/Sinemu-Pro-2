@extends('admin.layouts.app')

@php
    $pageTitle = 'Detail Verifikasi Klaim - SiNemu';
    $activeMenu = 'claim-verifications';
    $hideSearch = true;
    $hideSidebar = true;
    $topbarBackUrl = route('admin.claim-verifications');
    $topbarBackLabel = 'Kembali ke Verifikasi Klaim';
    $hasPelaporEmail = filter_var($pelaporEmail, FILTER_VALIDATE_EMAIL) !== false;
    $emailContactHref = $hasPelaporEmail ? ('mailto:' . $pelaporEmail) : '#';
    $contactSubject = rawurlencode('Tindak lanjut verifikasi klaim #' . $klaim->id);
    $contactBody = rawurlencode('Halo ' . $pelaporNama . ', kami ingin menindaklanjuti pengajuan klaim Anda.');
    $hubungiHref = $hasPelaporEmail
        ? ('mailto:' . $pelaporEmail . '?subject=' . $contactSubject . '&body=' . $contactBody)
        : '#';
    $ringkasanStatus = match ($klaim->status_klaim) {
        'pending' => 'Klaim masih menunggu keputusan admin. Pastikan data pelapor dan kecocokan barang sudah tervalidasi.',
        'disetujui' => 'Klaim telah disetujui. Lanjutkan koordinasi penyerahan barang kepada pemilik.',
        'ditolak' => 'Klaim telah ditolak. Pastikan alasan penolakan terdokumentasi dengan jelas.',
        default => 'Status klaim belum terdefinisi.',
    };
    $catatanPengaju = trim((string) ($klaim->laporanHilang?->keterangan ?? ''));
    $catatanAdmin = trim((string) ($klaim->catatan ?? ''));
@endphp

@section('page-content')
    <section class="claim-detail-page">
        <div class="claim-detail-header">
            <div>
                <p class="claim-detail-breadcrumb">
                    <a href="{{ route('admin.claim-verifications') }}">Verifikasi Klaim</a>
                    <span>/</span>
                    <strong>Detail Klaim</strong>
                </p>
                <h1>Detail Verifikasi Klaim</h1>
                <div class="claim-detail-header-meta">
                    <span>Klaim #{{ $klaim->id }}</span>
                    <span>Dibuat {{ $klaim->created_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                    <span>Diperbarui {{ $klaim->updated_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup success" data-autoclose="2800" style="--autoclose-ms: 2800ms;" role="status" aria-live="polite">
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

        <section class="claim-detail-layout">
            <article class="report-card claim-main-card">
                <header class="claim-main-head">
                    <div>
                        <span class="claim-chip-label">Status Klaim</span>
                        <h2>{{ $namaBarang }}</h2>
                    </div>
                    <span class="status-chip {{ $statusClass }}">{{ strtoupper($statusLabel) }}</span>
                </header>

                <div class="claim-main-grid">
                    <div class="claim-item-visual">
                        <span class="claim-item-visual-label">Foto Barang</span>
                        <img src="{{ $fotoUrl }}" alt="{{ $namaBarang }}" loading="lazy" decoding="async">
                    </div>
                    <div class="claim-item-info">
                        <div class="claim-info-grid">
                            <article class="claim-info-card">
                                <small>Kategori</small>
                                <strong>{{ $kategoriNama }}</strong>
                            </article>
                            <article class="claim-info-card">
                                <small>Lokasi</small>
                                <strong>{{ $lokasi }}</strong>
                            </article>
                            <article class="claim-info-card">
                                <small>Tanggal Laporan</small>
                                <strong>{{ \Illuminate\Support\Carbon::parse($tanggalLaporan)->translatedFormat('d F Y') }}</strong>
                            </article>
                            <article class="claim-info-card">
                                <small>ID Klaim</small>
                                <strong>#{{ $klaim->id }}</strong>
                            </article>
                        </div>
                        <article class="claim-description-box">
                            <h3>Deskripsi</h3>
                            <p>{{ $deskripsi }}</p>
                        </article>
                        <article class="claim-verification-summary">
                            <h3>Ringkasan Verifikasi</h3>
                            <p>{{ $ringkasanStatus }}</p>
                        </article>
                    </div>
                </div>
            </article>

            <aside class="claim-side-column">
                <article class="report-card claim-side-card claim-panel-status">
                    <header><h2>Status & Riwayat</h2></header>
                    <div class="claim-side-body">
                        <div class="claim-status-current">
                            <small>Status Saat Ini</small>
                            <span class="status-chip {{ $statusClass }}">{{ strtoupper($statusLabel) }}</span>
                        </div>
                        <div class="claim-status-current">
                            <small>Status Barang Terkait</small>
                            <span class="status-chip {{ $statusBarangClass }}">{{ strtoupper($statusBarangLabel) }}</span>
                        </div>
                        <ul class="claim-timeline">
                            <li>
                                <strong>Klaim diajukan</strong>
                                <span>{{ $klaim->created_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                            </li>
                            <li>
                                <strong>Status klaim saat ini</strong>
                                <span>{{ strtoupper($statusLabel) }}</span>
                            </li>
                            <li>
                                <strong>Terakhir diperbarui</strong>
                                <span>{{ $klaim->updated_at?->translatedFormat('d M Y, H:i') }} WIB</span>
                            </li>
                        </ul>
                        @if($catatanPengaju !== '')
                            <div class="claim-note-box claim-note-requester">
                                <small>Catatan Pelapor</small>
                                <p>{{ $catatanPengaju }}</p>
                            </div>
                        @endif
                        @if($catatanAdmin !== '')
                            <div class="claim-note-box">
                                <small>Catatan Admin</small>
                                <p>{{ $catatanAdmin }}</p>
                            </div>
                        @endif
                    </div>
                </article>

                <article class="report-card claim-side-card claim-panel-requester">
                    <header><h2>Informasi Pengaju</h2></header>
                    <div class="claim-side-body">
                        <strong>{{ $pelaporNama }}</strong>
                        <small>{{ $pelaporEmail }}</small>
                        <div class="claim-contact-actions">
                            <a href="{{ $hubungiHref }}" class="filter-btn {{ $hasPelaporEmail ? '' : 'is-disabled' }}">Hubungi</a>
                            <a href="{{ $emailContactHref }}" class="filter-btn {{ $hasPelaporEmail ? '' : 'is-disabled' }}">Email</a>
                        </div>
                    </div>
                </article>
            </aside>
        </section>

        @if($klaim->status_klaim === 'pending')
            <div class="claim-detail-bottom-actions">
                <form method="POST" action="{{ route('admin.claim-verifications.reject', $klaim->id) }}" data-confirm-delete data-confirm-message="Tolak klaim ini? Pastikan alasan penolakan sudah sesuai.">
                    @csrf
                    <button type="submit" class="claim-action-btn danger">Tolak Klaim</button>
                </form>
                <form method="POST" action="{{ route('admin.claim-verifications.approve', $klaim->id) }}" data-confirm-delete data-confirm-message="Setujui klaim ini? Barang akan ditandai sesuai proses verifikasi.">
                    @csrf
                    <button type="submit" class="claim-action-btn success">Setujui Klaim</button>
                </form>
            </div>
        @endif
    </section>
@endsection
