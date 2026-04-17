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
    $ciriKhususPengaju = trim((string) ($klaim->laporanHilang?->ciri_khusus ?? ''));
    $buktiKepemilikanPengaju = trim((string) ($klaim->laporanHilang?->bukti_kepemilikan ?? ''));
    $buktiFotoUrls = collect((array) ($klaim->bukti_foto ?? []))
        ->filter(fn ($path) => is_string($path) && trim($path) !== '')
        ->map(function ($path) {
            $cleanPath = str_replace('\\', '/', ltrim((string) $path, '/'));
            if ($cleanPath === '') {
                return null;
            }

            if (\Illuminate\Support\Str::startsWith($cleanPath, ['http://', 'https://'])) {
                return $cleanPath;
            }

            if (\Illuminate\Support\Str::startsWith($cleanPath, 'storage/')) {
                $cleanPath = substr($cleanPath, 8);
            } elseif (\Illuminate\Support\Str::startsWith($cleanPath, 'public/')) {
                $cleanPath = substr($cleanPath, 7);
            }

            [$folder, $subPath] = array_pad(explode('/', $cleanPath, 2), 2, '');
            return in_array($folder, ['barang-hilang', 'barang-temuan', 'verifikasi-klaim', 'profil-admin', 'profil-user'], true) && $subPath !== ''
                ? route('media.image', ['folder' => $folder, 'path' => $subPath])
                : asset('storage/' . $cleanPath);
        })
        ->filter()
        ->values();
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
                        @if($ciriKhususPengaju !== '')
                            <div class="claim-note-box claim-note-requester">
                                <small>Ciri Unik Barang</small>
                                <p>{{ $ciriKhususPengaju }}</p>
                            </div>
                        @endif
                        @if($buktiKepemilikanPengaju !== '')
                            <div class="claim-note-box claim-note-requester">
                                <small>Bukti Kepemilikan</small>
                                <p>{{ $buktiKepemilikanPengaju }}</p>
                            </div>
                        @endif
                        @if($buktiFotoUrls->isNotEmpty())
                            <div class="claim-proof-gallery">
                                <small>Foto Bukti Kepemilikan</small>
                                <div class="claim-proof-grid">
                                    @foreach($buktiFotoUrls as $proofUrl)
                                        <a href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer" class="claim-proof-item">
                                            <img src="{{ $proofUrl }}" alt="Bukti kepemilikan klaim #{{ $klaim->id }}" loading="lazy" decoding="async">
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if($catatanAdmin !== '')
                            <div class="claim-note-box">
                                <small>Catatan Klaim Tambahan</small>
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
                <form method="POST" action="{{ route('admin.claim-verifications.reject', $klaim->id) }}"
                    data-confirm-delete
                    data-confirm-title="Konfirmasi Tolak Klaim"
                    data-confirm-message="Tolak klaim ini? Pastikan alasan penolakan sudah sesuai."
                    data-confirm-submit-label="Tolak Klaim"
                    data-confirm-submit-variant="danger">
                    @csrf
                    <button type="submit" class="claim-action-btn danger">Tolak Klaim</button>
                </form>
                <form method="POST" action="{{ route('admin.claim-verifications.approve', $klaim->id) }}"
                    data-confirm-delete
                    data-confirm-title="Konfirmasi Setujui Klaim"
                    data-confirm-message="Setujui klaim ini? Barang akan ditandai sesuai proses verifikasi."
                    data-confirm-submit-label="Setujui Klaim"
                    data-confirm-submit-variant="primary">
                    @csrf
                    <button type="submit" class="claim-action-btn success">Setujui Klaim</button>
                </form>
            </div>
        @endif
    </section>
@endsection
