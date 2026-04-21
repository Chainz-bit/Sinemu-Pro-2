@extends('super.layouts.app')

@php
    $pageTitle = 'Daftar Admin - Super Admin';
    $activeMenu = 'admins';
    $searchAction = route('super.admins.index');
    $searchPlaceholder = 'Cari nama admin, instansi, atau kecamatan';
@endphp

@section('page-content')
    <div class="dashboard-page-content super-page-content">
        <section class="intro">
            <h1>Daftar Admin Terdaftar</h1>
            <p>Pantau semua admin yang sudah masuk ke sistem, termasuk status aktif, antrean verifikasi, dan akun yang perlu revisi.</p>
        </section>

        <section class="stats-grid super-stats-grid compact">
            <article class="stat-card stat-card-found">
                <div class="stat-card-head">
                    <span>Total Admin</span>
                    <div class="stat-card-icon"><iconify-icon icon="mdi:account-group-outline"></iconify-icon></div>
                </div>
                <strong>{{ $summary['total'] ?? 0 }}</strong>
                <small>Total akun admin yang terdaftar.</small>
            </article>
            <article class="stat-card stat-card-claim">
                <div class="stat-card-head">
                    <span>Menunggu</span>
                    <div class="stat-card-icon"><iconify-icon icon="mdi:clock-alert-outline"></iconify-icon></div>
                </div>
                <strong>{{ $summary['pending'] ?? 0 }}</strong>
                <small>Pendaftar yang butuh keputusan.</small>
            </article>
            <article class="stat-card stat-card-found">
                <div class="stat-card-head">
                    <span>Aktif</span>
                    <div class="stat-card-icon"><iconify-icon icon="mdi:check-decagram-outline"></iconify-icon></div>
                </div>
                <strong>{{ $summary['active'] ?? 0 }}</strong>
                <small>Admin yang sudah lolos verifikasi.</small>
            </article>
            <article class="stat-card stat-card-lost">
                <div class="stat-card-head">
                    <span>Ditolak</span>
                    <div class="stat-card-icon"><iconify-icon icon="mdi:close-octagon-outline"></iconify-icon></div>
                </div>
                <strong>{{ $summary['rejected'] ?? 0 }}</strong>
                <small>Akun yang ditolak atau perlu revisi.</small>
            </article>
        </section>

        <section class="report-card dashboard-report-card">
            <header>
                <div class="report-heading">
                    <h2>Direktori Admin</h2>
                    <p>Telusuri daftar admin berdasarkan nama, instansi, kecamatan, atau status verifikasi.</p>
                </div>
                <div class="report-actions">
                    <form method="GET" action="{{ route('super.admins.index') }}" class="dashboard-filter-form">
                        @if($search !== '')
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <select name="status" class="filter-btn dashboard-filter-select" onchange="this.form.submit()">
                            <option value="semua" @selected($statusFilter === 'semua')>Semua Status</option>
                            <option value="pending" @selected($statusFilter === 'pending')>Menunggu</option>
                            <option value="active" @selected($statusFilter === 'active')>Aktif</option>
                            <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak</option>
                        </select>
                    </form>
                </div>
            </header>

            <div class="dashboard-table-toolbar">
                <div class="dashboard-quick-filters">
                    <a href="{{ route('super.admins.index', array_filter(['search' => $search, 'status' => 'semua'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'semua' ? 'is-active' : '' }}">Semua</a>
                    <a href="{{ route('super.admins.index', array_filter(['search' => $search, 'status' => 'pending'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'pending' ? 'is-active' : '' }}">Menunggu</a>
                    <a href="{{ route('super.admins.index', array_filter(['search' => $search, 'status' => 'active'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'active' ? 'is-active' : '' }}">Aktif</a>
                    <a href="{{ route('super.admins.index', array_filter(['search' => $search, 'status' => 'rejected'])) }}" class="dashboard-filter-chip {{ $statusFilter === 'rejected' ? 'is-active' : '' }}">Ditolak</a>
                </div>
                <div class="dashboard-toolbar-note">
                    Menampilkan {{ $admins->total() }} admin
                </div>
            </div>

            <div class="report-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Detail Admin</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $index => $admin)
                            @php
                                $statusKey = \App\Support\AdminVerificationStatusPresenter::key($admin->status_verifikasi);
                            @endphp
                            <tr>
                                <td>
                                    <div class="item-cell">
                                        <div class="item-avatar avatar-claim">
                                            <span class="item-avatar-fallback">{{ strtoupper(substr((string) $admin->nama, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <strong>{{ $admin->nama }}</strong>
                                            <small>{{ $admin->email }} | {{ $admin->instansi ?: 'Instansi belum diisi' }}</small>
                                            <small>{{ $admin->kecamatan ?: 'Kecamatan belum diisi' }} | {{ $admin->username }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <strong>{{ optional($admin->created_at)->format('d M Y') ?? '-' }}</strong>
                                        <small>{{ optional($admin->created_at)->format('H:i') ?? '-' }} WIB</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-chip {{ \App\Support\AdminVerificationStatusPresenter::badgeClass($statusKey) }}">
                                        {{ \App\Support\AdminVerificationStatusPresenter::label($statusKey) }}
                                    </span>
                                </td>
                                <td class="menu-cell">
                                    <button type="button" class="row-menu-trigger" data-menu-target="super-admin-menu-{{ $index }}" aria-label="Aksi">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 5.5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3zm0 5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3zm0 5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <div class="row-menu" id="super-admin-menu-{{ $index }}">
                                        <a href="{{ route('super.admins.index', ['search' => $admin->nama]) }}">Lihat Detail</a>
                                        <a href="{{ route('super.admin-verifications.index', ['search' => $admin->nama]) }}">Kelola Verifikasi</a>
                                    </div>
                                </td>
                            </tr>
                            @if($search !== '' && str_contains(strtolower($admin->nama . ' ' . $admin->email . ' ' . $admin->instansi . ' ' . $admin->kecamatan), strtolower($search)))
                                <tr class="super-admin-detail-row">
                                    <td colspan="4">
                                        <div class="super-admin-detail-card">
                                            <div><span>Alamat</span><strong>{{ $admin->alamat_lengkap ?: '-' }}</strong></div>
                                            <div><span>Telepon</span><strong>{{ $admin->nomor_telepon ?: '-' }}</strong></div>
                                            <div><span>Catatan Penolakan</span><strong>{{ $admin->alasan_penolakan ?: '-' }}</strong></div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" class="empty-row">Belum ada admin yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

