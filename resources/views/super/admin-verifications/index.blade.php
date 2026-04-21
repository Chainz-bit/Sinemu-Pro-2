@extends('super.layouts.app')

@php
    $pageTitle = 'Verifikasi Admin - Super Admin';
    $activeMenu = 'admin-verifications';
    $searchAction = route('super.admin-verifications.index');
    $searchPlaceholder = 'Cari nama admin, instansi, atau kecamatan';
    $normalizedStatusFilter = $statusFilter ?? 'pending';
    $activeFilterLabel = match ($normalizedStatusFilter) {
        'active' => 'Aktif',
        'rejected' => 'Ditolak',
        'semua' => 'Semua',
        default => 'Menunggu',
    };
    $isFiltered = $normalizedStatusFilter !== 'semua' || !empty($search ?? '');
@endphp

@section('page-content')
    <div class="dashboard-page-content super-page-content super-verification-page">
        <section class="intro">
            <h1>Verifikasi Admin</h1>
            <p>Tinjau kualitas data pendaftar, setujui admin yang layak, dan beri alasan jelas saat menolak pendaftaran.</p>
        </section>

        <section class="super-dashboard-grid super-dashboard-grid-single">
            <article class="report-card">
                <header>
                    <div class="report-heading">
                        <h2>Ringkasan Status</h2>
                        <p>Distribusi status membantu menentukan prioritas verifikasi.</p>
                    </div>
                </header>
                <div class="super-status-summary">
                    @foreach([
                        ['label' => 'Menunggu', 'value' => $summary['pending'] ?? 0, 'status' => 'pending', 'icon' => 'mdi:clock-alert-outline'],
                        ['label' => 'Aktif', 'value' => $summary['active'] ?? 0, 'status' => 'active', 'icon' => 'mdi:check-decagram-outline'],
                        ['label' => 'Ditolak', 'value' => $summary['rejected'] ?? 0, 'status' => 'rejected', 'icon' => 'mdi:close-octagon-outline'],
                    ] as $card)
                        <a
                            href="{{ route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => $card['status']])) }}#daftar-verifikasi"
                            class="stat-card super-status-card-link {{ \App\Support\AdminVerificationStatusPresenter::cardClass($card['status']) }} {{ $normalizedStatusFilter === $card['status'] ? 'is-active' : '' }}"
                        >
                            <div class="stat-card-head">
                                <span>{{ $card['label'] }}</span>
                                <div class="stat-card-icon">
                                    <iconify-icon icon="{{ $card['icon'] }}"></iconify-icon>
                                </div>
                            </div>
                            <strong>{{ $card['value'] }}</strong>
                            <small>{{ \App\Support\AdminVerificationStatusPresenter::description($card['status']) }}</small>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>

        <section id="daftar-verifikasi" class="report-card dashboard-report-card {{ $admins->total() === 0 ? 'is-empty' : '' }}">
            <header>
                <div class="report-heading">
                    <h2>Daftar Verifikasi</h2>
                    <p>Fokus utama halaman ini adalah review dan keputusan verifikasi admin.</p>
                </div>
            </header>

            <div class="dashboard-table-toolbar">
                <div class="dashboard-quick-filters">
                    <a href="{{ route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => 'pending'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'pending' ? 'is-active' : '' }}">Menunggu</a>
                    <a href="{{ route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => 'active'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'active' ? 'is-active' : '' }}">Aktif</a>
                    <a href="{{ route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => 'rejected'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'rejected' ? 'is-active' : '' }}">Ditolak</a>
                    <a href="{{ route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => 'semua'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'semua' ? 'is-active' : '' }}">Semua</a>
                    @if($isFiltered)
                        <a href="{{ route('super.admin-verifications.index', ['status' => 'semua']) }}" class="dashboard-filter-chip">Reset Filter</a>
                    @endif
                </div>
                <div class="dashboard-toolbar-note">
                    Menampilkan {{ $admins->total() }} admin
                </div>
            </div>

            <div class="super-verification-stack {{ $admins->total() === 0 ? 'is-empty' : '' }}">
                @forelse($admins as $admin)
                    @php
                        $statusKey = \App\Support\AdminVerificationStatusPresenter::key($admin->status_verifikasi);
                    @endphp
                    <article class="super-verification-card">
                        <div class="super-verification-head">
                            <div>
                                <h3>{{ $admin->nama }}</h3>
                                <p>{{ $admin->email }} | {{ $admin->username }}</p>
                            </div>
                            <span class="status-chip {{ \App\Support\AdminVerificationStatusPresenter::badgeClass($statusKey) }}">
                                {{ \App\Support\AdminVerificationStatusPresenter::label($statusKey) }}
                            </span>
                        </div>

                        <div class="super-verification-grid">
                            <div class="super-admin-detail-card">
                                <div><span>Instansi</span><strong>{{ $admin->instansi ?: '-' }}</strong></div>
                                <div><span>Kecamatan</span><strong>{{ $admin->kecamatan ?: '-' }}</strong></div>
                                <div><span>Telepon</span><strong>{{ $admin->nomor_telepon ?: '-' }}</strong></div>
                                <div><span>Tanggal Daftar</span><strong>{{ optional($admin->created_at)->format('d M Y H:i') ?? '-' }}</strong></div>
                            </div>
                            <div class="super-admin-detail-card">
                                <div><span>Alamat Lengkap</span><strong>{{ $admin->alamat_lengkap ?: '-' }}</strong></div>
                                <div><span>Catatan Penolakan</span><strong>{{ $admin->alasan_penolakan ?: '-' }}</strong></div>
                            </div>
                        </div>

                        @if($statusKey === 'pending')
                            <div class="super-verification-actions">
                                <form method="POST" action="{{ route('super.admin-verifications.accept', $admin->id) }}">
                                    @csrf
                                    <button type="submit" class="super-inline-btn is-accept">Setujui Admin</button>
                                </form>
                                <form method="POST" action="{{ route('super.admin-verifications.reject', $admin->id) }}" class="super-reject-form">
                                    @csrf
                                    <textarea name="alasan_penolakan" rows="3" placeholder="Tuliskan alasan penolakan atau revisi jika diperlukan."></textarea>
                                    <button type="submit" class="super-inline-btn is-reject">Tolak Admin</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="claim-create-empty super-empty-panel super-empty-panel-center">
                        <iconify-icon icon="mdi:account-search-outline"></iconify-icon>
                        <strong>Tidak ada data verifikasi</strong>
                        <p>Filter aktif: <b>{{ $activeFilterLabel }}</b>. Coba ubah ke <b>Semua</b> atau sesuaikan kata kunci pencarian.</p>
                        <a href="{{ route('super.admin-verifications.index', ['status' => 'semua']) }}" class="super-inline-btn">Tampilkan Semua Data</a>
                    </div>
                @endforelse
            </div>

            <footer class="pagination">
                @if($admins->onFirstPage())
                    <button type="button" disabled>Sebelumnya</button>
                @else
                    <button type="button" onclick="window.location.href='{{ $admins->previousPageUrl() }}'">Sebelumnya</button>
                @endif

                @for($page = 1; $page <= $admins->lastPage(); $page++)
                    <button type="button" class="{{ $admins->currentPage() === $page ? 'active' : '' }}" onclick="window.location.href='{{ $admins->url($page) }}'">{{ $page }}</button>
                @endfor

                @if($admins->hasMorePages())
                    <button type="button" onclick="window.location.href='{{ $admins->nextPageUrl() }}'">Selanjutnya</button>
                @else
                    <button type="button" disabled>Selanjutnya</button>
                @endif
            </footer>
        </section>
    </div>
@endsection
