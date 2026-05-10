@extends('super.layouts.app')

@php
    $pageTitle = 'Dashboard Super Admin - SiNemu';
    $activeMenu = 'dashboard';
    $searchAction = route('super.admins.index');
    $searchPlaceholder = 'Cari kecamatan atau instansi';
    $managerRoleLabel = \App\Support\RoleLabels::manager();
    $managerRoleLabelLower = \App\Support\RoleLabels::managerLower();
@endphp

@section('page-content')
    @php
        $hasPriorityAdmins = ($summary['pending'] ?? 0) > 0 && $priorityAdmins->isNotEmpty();
    @endphp
    <div class="dashboard-page-content super-page-content">
        <section class="intro">
            <h1>Ringkasan Dashboard Super Admin</h1>
            <p>Kontrol verifikasi {{ $managerRoleLabelLower }}, pantau pertumbuhan pendaftar, dan fokus ke akun yang perlu ditindak segera.</p>
        </section>

        <section class="stats-grid super-stats-grid">
            <x-dashboard.stat-card class="super-stat-card super-stat-card-primary" :label="'Total ' . $managerRoleLabel . ' Terdaftar'" :value="$summary['total'] ?? 0" icon="mdi:account-group-outline" :description="'Semua akun ' . $managerRoleLabelLower . ' yang pernah terdaftar di sistem.'" />
            <x-dashboard.stat-card class="super-stat-card super-stat-card-warning" label="Menunggu Verifikasi" :value="$summary['pending'] ?? 0" icon="mdi:clock-alert-outline" description="Pendaftar yang perlu keputusan super admin." />
            <x-dashboard.stat-card class="super-stat-card super-stat-card-danger" label="Ditolak / Revisi" :value="$summary['rejected'] ?? 0" icon="mdi:close-octagon-outline" description="Akun yang ditolak dan menunggu perbaikan data." />
            <x-dashboard.stat-card class="super-stat-card super-stat-card-success" :label="$managerRoleLabel . ' Baru 7 Hari'" :value="$summary['newThisWeek'] ?? 0" icon="mdi:chart-timeline-variant" :description="'Pertumbuhan pendaftaran ' . $managerRoleLabelLower . ' selama satu minggu terakhir.'" />
        </section>

        <section class="super-dashboard-focus">
            <article class="report-card super-activity-card super-focus-card">
                <header>
                    <div class="report-heading">
                        <h2>Aktivitas Verifikasi</h2>
                        <p>Perubahan status terbaru dari proses approval {{ $managerRoleLabelLower }}.</p>
                    </div>
                    <div class="report-actions">
                        <a href="{{ route('super.admin-verifications.index', ['status' => 'semua']) }}#daftar-verifikasi">Riwayat Verifikasi</a>
                    </div>
                </header>

                <div class="super-activity-list">
                    @forelse($latestActivities as $admin)
                        @php
                            $statusKey = \App\Support\AdminVerificationStatusPresenter::key($admin->status_verifikasi);
                        @endphp
                        <article class="super-activity-item">
                            <span class="status-chip {{ \App\Support\AdminVerificationStatusPresenter::badgeClass($statusKey) }}">
                                {{ \App\Support\AdminVerificationStatusPresenter::label($statusKey) }}
                            </span>
                            <div>
                                <strong>{{ $admin->nama }}</strong>
                                <small>
                                    {{ $admin->instansi ?: 'Instansi belum diisi' }}
                                    |
                                    {{ $admin->verified_at?->format('d M Y H:i') ?? optional($admin->updated_at)->format('d M Y H:i') ?? '-' }}
                                </small>
                            </div>
                            <a class="super-activity-link" href="{{ route('super.admin-verifications.index', ['search' => $admin->nama]) }}">Buka</a>
                        </article>
                    @empty
                        <x-dashboard.empty-state
                            icon="mdi:timeline-clock-outline"
                            title="Belum ada aktivitas"
                            message="Riwayat verifikasi akan muncul setelah super admin mulai memproses data."
                        />
                    @endforelse
                </div>
            </article>

            <section class="report-card dashboard-report-card super-focus-card">
                <header>
                    <div class="report-heading">
                        <h2>{{ $managerRoleLabel }} Baru Terdaftar</h2>
                        <p>Gunakan daftar ini untuk memantau pendaftar terbaru dan membuka data lengkap.</p>
                    </div>
                    <div class="report-actions">
                        <a href="{{ route('super.admins.index') }}">Lihat Semua</a>
                    </div>
                </header>

                <div class="report-table-wrap">
                    <table class="report-table responsive-card-table">
                        <thead>
                            <tr>
                                <th>Detail {{ $managerRoleLabel }}</th>
                                <th>Tanggal Daftar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($newestAdmins as $index => $admin)
                                @php
                                    $statusKey = \App\Support\AdminVerificationStatusPresenter::key($admin->status_verifikasi);
                                @endphp
                                <tr>
                                    <td class="card-primary-cell" data-label="Detail Aktivitas">
                                        <div class="item-cell">
                                            <div class="item-avatar avatar-claim">
                                                <span class="item-avatar-fallback">{{ strtoupper(substr((string) $admin->nama, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <strong>{{ $admin->nama }}</strong>
                                                <small>{{ $admin->instansi ?: 'Instansi belum diisi' }} | {{ $admin->kecamatan ?: 'Kecamatan belum diisi' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="card-date-cell" data-label="Tanggal">
                                        <div class="date-cell">
                                            <strong>{{ optional($admin->created_at)->format('d M Y') ?? '-' }}</strong>
                                            <small>{{ optional($admin->created_at)->format('H:i') ?? '-' }} WIB</small>
                                        </div>
                                    </td>
                                    <td class="card-status-cell" data-label="Status">
                                        <span class="status-chip {{ \App\Support\AdminVerificationStatusPresenter::badgeClass($statusKey) }}">
                                            {{ \App\Support\AdminVerificationStatusPresenter::label($statusKey) }}
                                        </span>
                                    </td>
                                    <td class="menu-cell card-action-cell" data-label="Aksi">
                                        <x-dashboard.action-menu id="super-dashboard-menu-{{ $index }}">
                                            <a href="{{ route('super.admins.index', ['search' => $admin->nama]) }}">Lihat Detail</a>
                                            <a href="{{ route('super.admin-verifications.index', ['search' => $admin->nama]) }}">Buka Verifikasi</a>
                                        </x-dashboard.action-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr class="super-table-empty-row">
                                    <td colspan="4" class="empty-row">
                                        <x-dashboard.empty-state
                                            class="super-table-empty-state"
                                            icon="mdi:account-search-outline"
                                            :title="'Belum ada ' . $managerRoleLabelLower . ' terdaftar'"
                                            :message="'Data ' . $managerRoleLabelLower . ' baru akan muncul di sini setelah ada pendaftaran akun ' . $managerRoleLabelLower . '.'"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        @if($hasPriorityAdmins)
            <section class="super-dashboard-grid">
                <article class="report-card super-priority-card">
                    <header>
                        <div class="report-heading">
                            <h2>Butuh Tindakan Sekarang</h2>
                            <p>Urutkan penyelesaian berdasarkan antrean pendaftar yang paling baru.</p>
                        </div>
                        <div class="report-actions">
                            <a href="{{ route('super.admin-verifications.index', ['status' => 'pending']) }}#daftar-verifikasi">Lihat Semua Pending</a>
                        </div>
                    </header>

                    <div class="super-priority-list">
                        @foreach($priorityAdmins as $admin)
                            <article class="super-priority-item">
                                <div class="super-priority-meta">
                                    <strong>{{ $admin->nama }}</strong>
                                    <small>{{ $admin->instansi ?: 'Instansi belum diisi' }} | {{ $admin->kecamatan ?: 'Kecamatan belum diisi' }}</small>
                                    <small>Didaftarkan {{ optional($admin->created_at)->diffForHumans() ?? '-' }}</small>
                                </div>
                                <div class="super-priority-actions">
                                    <form method="POST" action="{{ route('super.admin-verifications.accept', $admin->id) }}">
                                        @csrf
                                        <button type="submit" class="super-inline-btn is-accept">Setujui</button>
                                    </form>
                                    <a href="{{ route('super.admin-verifications.index', ['search' => $admin->nama]) }}" class="super-inline-btn">Tinjau Detail</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </article>
            </section>
        @endif
    </div>

    <script>
        document.body.classList.remove('dashboard-fixed-mode');
        document.body.classList.add('super-dashboard-page-mode');
    </script>
@endsection
