@extends('super.layouts.app')

@php
    $managerRoleLabel = \App\Support\RoleLabels::manager();
    $managerRoleLabelLower = \App\Support\RoleLabels::managerLower();
    $pageTitle = 'Verifikasi ' . $managerRoleLabel . ' - Super Admin';
    $activeMenu = 'admin-verifications';
    $searchAction = route('super.admin-verifications.index');
    $searchPlaceholder = 'Cari nama ' . $managerRoleLabelLower . ', instansi, atau kecamatan';
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
            <h1>Verifikasi {{ $managerRoleLabel }}</h1>
            <p>Tinjau kualitas data pendaftar, setujui {{ $managerRoleLabelLower }} yang layak, dan beri alasan jelas saat menolak pendaftaran.</p>
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
                        <x-dashboard.stat-card
                            class="super-status-card-link {{ \App\Support\AdminVerificationStatusPresenter::cardClass($card['status']) }}"
                            :href="route('super.admin-verifications.index', array_filter(['search' => $search, 'status' => $card['status']])) . '#daftar-verifikasi'"
                            :active="$normalizedStatusFilter === $card['status']"
                            :label="$card['label']"
                            :value="$card['value']"
                            :icon="$card['icon']"
                            :description="\App\Support\AdminVerificationStatusPresenter::description($card['status'])"
                        />
                    @endforeach
                </div>
            </article>
        </section>

        <section id="daftar-verifikasi" class="report-card dashboard-report-card {{ $admins->total() === 0 ? 'is-empty' : '' }}">
            <header>
                <div class="report-heading">
                    <h2>Daftar Verifikasi</h2>
                    <p>Fokus utama halaman ini adalah review dan keputusan verifikasi {{ $managerRoleLabelLower }}.</p>
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
                    Menampilkan {{ $admins->total() }} {{ $managerRoleLabelLower }}
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
                            <div class="super-manager-detail-card">
                                <div><span>Instansi</span><strong>{{ $admin->instansi ?: '-' }}</strong></div>
                                <div><span>Kecamatan</span><strong>{{ $admin->kecamatan ?: '-' }}</strong></div>
                                <div><span>Telepon</span><strong>{{ $admin->nomor_telepon ?: '-' }}</strong></div>
                                <div><span>Tanggal Daftar</span><strong>{{ optional($admin->created_at)->format('d M Y H:i') ?? '-' }}</strong></div>
                            </div>
                            <div class="super-manager-detail-card">
                                <div><span>Alamat Lengkap</span><strong>{{ $admin->alamat_lengkap ?: '-' }}</strong></div>
                                <div><span>Catatan Penolakan</span><strong>{{ $admin->alasan_penolakan ?: '-' }}</strong></div>
                            </div>
                        </div>

                        @if($statusKey === 'pending')
                            <div class="super-verification-actions">
                                <form method="POST" action="{{ route('super.admin-verifications.accept', $admin->id) }}">
                                    @csrf
                                    <button type="submit" class="super-inline-btn is-accept">Setujui {{ $managerRoleLabel }}</button>
                                </form>
                                <form method="POST" action="{{ route('super.admin-verifications.reject', $admin->id) }}" class="super-reject-form">
                                    @csrf
                                    <textarea name="alasan_penolakan" rows="3" placeholder="Tuliskan alasan penolakan atau revisi jika diperlukan."></textarea>
                                    <button type="submit" class="super-inline-btn is-reject">Tolak {{ $managerRoleLabel }}</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <x-dashboard.empty-state
                        class="super-empty-panel-center"
                        icon="mdi:account-search-outline"
                        title="Tidak ada data verifikasi"
                        :message="'Filter aktif: <b>' . e($activeFilterLabel) . '</b>. Coba ubah ke <b>Semua</b> atau sesuaikan kata kunci pencarian.'"
                        :action-url="route('super.admin-verifications.index', ['status' => 'semua'])"
                        action-label="Tampilkan Semua Data"
                    />
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
