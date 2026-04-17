@extends('admin.layouts.app')

@php
    $pageTitle = 'Profil Saya - Admin - SiNemu';
    $activeMenu = 'profile';
    $hideSearch = true;
    $hideSidebar = true;
    $topbarBackUrl = route('admin.dashboard');
    $topbarBackLabel = 'Kembali ke Dashboard';
@endphp

@section('page-content')
    <div class="profile-page-content">
<section class="profile-account-card">
            <div class="profile-account-top">
                <div class="profile-account-main">
                    <img src="{{ $profileAvatar }}" alt="Foto profil {{ $admin?->nama ?? 'Admin' }}" class="profile-account-avatar" onerror="this.onerror=null;this.src='{{ asset('img/profil.jpg') }}';">
                    <div class="profile-account-meta">
                        <div class="profile-account-name-wrap">
                            <h2>{{ $admin?->nama ?? 'Admin' }}</h2>
                        </div>
                        <p class="profile-role">Admin SiNemu • Pengelola Operasional</p>
                        <div class="profile-account-contact">
                            <span>{{ $admin?->email ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="{{ route('admin.profile.edit') }}" class="profile-action-secondary">
                        Edit Profil
                    </a>
                </div>
            </div>

            <div class="profile-admin-info-grid">
                <article class="profile-info-card">
                    <span class="profile-info-label">Akun</span>
                    <strong class="profile-info-value">{{ $admin?->username ?? '-' }}</strong>
                </article>
                <article class="profile-info-card">
                    <span class="profile-info-label">Instansi Aktif</span>
                    <strong class="profile-info-value">{{ $admin?->instansi ?? '-' }}</strong>
                </article>
                <article class="profile-info-card">
                    <span class="profile-info-label">Wilayah</span>
                    <strong class="profile-info-value">{{ $admin?->kecamatan ?? '-' }}</strong>
                </article>
                <article class="profile-info-card">
                    <span class="profile-info-label">Alamat Operasional</span>
                    <strong class="profile-info-value">{{ $admin?->alamat_lengkap ?? '-' }}</strong>
                </article>
            </div>
        </section>

        <section class="profile-stats-grid">
            <article class="profile-stat-card">
                <span>Laporan Diajukan</span>
                <strong>{{ $laporanDiajukan }}</strong>
                <small>Total input oleh akun ini</small>
            </article>
            <article class="profile-stat-card">
                <span>Klaim Menunggu</span>
                <strong>{{ $klaimMenunggu }}</strong>
                <small>Perlu tindak lanjut verifikasi</small>
            </article>
            <article class="profile-stat-card">
                <span>Selesai Ditangani</span>
                <strong>{{ $selesaiDitangani }}</strong>
                <small>Klaim sudah diputuskan admin</small>
            </article>
        </section>

        <section class="report-card profile-activity-card">
            <header>
                <h2>Aktivitas Terbaru</h2>
                <a href="{{ route('admin.dashboard') }}">Buka Dashboard</a>
            </header>

            <div class="profile-activity-list">
                @forelse($recentActivities as $activity)
                    <article class="profile-activity-item">
                        <div class="profile-activity-main">
                            <h3>{{ $activity->title }}</h3>
                            <p>
                                {{ \Carbon\Carbon::parse($activity->timestamp)->translatedFormat('d M Y, H:i') }} WIB
                            </p>
                        </div>
                        <div class="profile-activity-right">
                            <span class="status-chip status-{{ $activity->status_class }}">{{ $activity->status_label }}</span>
                            <a href="{{ $activity->detail_url }}">Lihat Detail</a>
                        </div>
                    </article>
                @empty
                    <div class="profile-activity-empty">Belum ada aktivitas terbaru untuk ditampilkan.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
