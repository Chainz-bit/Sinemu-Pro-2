@extends('admin.layouts.app')

@php
    /** BAGIAN: Meta Halaman */
    $pageTitle = 'Dashboard Admin - SiNemu';
    $activeMenu = 'dashboard';
    $searchAction = route('admin.dashboard');
    $searchPlaceholder = 'Cari laporan atau barang';
@endphp

@section('page-content')
    {{-- BAGIAN: Pembuka --}}
    <section class="intro">
        <h1>Ringkasan Dashboard Admin</h1>
        <p>Selamat Datang, {{ $admin?->nama ?? 'Admin' }}! Kelola barang hilang &amp; temuan dengan efisien.</p>
    </section>

    {{-- BAGIAN: Kartu Statistik --}}
    <section class="stats-grid">
        <article class="stat-card">
            <span>Total Laporan Hilang</span>
            <strong>{{ $totalHilang }}</strong>
        </article>
        <article class="stat-card">
            <span>Total Laporan Temuan</span>
            <strong>{{ $totalTemuan }}</strong>
        </article>
        <article class="stat-card">
            <span>Menunggu Verifikasi</span>
            <strong>{{ $menungguVerifikasi }}</strong>
        </article>
    </section>

    {{-- BAGIAN: Tabel Laporan Terbaru --}}
    <section class="report-card">
        <header>
            <h2>Laporan Terbaru</h2>
            <div class="report-actions">
                <button type="button" class="filter-btn">Filter</button>
                <a href="#">Lihat Semua</a>
            </div>
        </header>

        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Detail Barang</th>
                        <th>Tanggal Laporan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestReports as $index => $report)
                        <tr>
                            <td>
                                <div class="item-cell">
                                    <div class="item-avatar {{ $report->avatar_class ?? '' }}">
                                        {{ $report->avatar ?? '?' }}
                                    </div>
                                    <div>
                                        <strong>{{ $report->item_name ?? '-' }}</strong>
                                        <small>{{ $report->item_detail ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="date-cell">
                                    <strong>
                                        {{ !empty($report->incident_date) ? \Carbon\Carbon::parse($report->incident_date)->format('d M Y') : '-' }}
                                    </strong>
                                    <small>
                                        {{ !empty($report->created_at) ? \Carbon\Carbon::parse($report->created_at)->format('H:i') : '-' }} WIB
                                    </small>
                                </div>
                            </td>

                            <td>
                                <span class="status-chip status-{{ $report->status ?? 'default' }}">
                                    {{ strtoupper(str_replace('_', ' ', $report->status ?? 'TIDAK DIKETAHUI')) }}
                                </span>
                            </td>

                            <td class="menu-cell">
                                <button
                                    type="button"
                                    class="row-menu-trigger"
                                    data-menu-target="menu-{{ $index }}"
                                    aria-label="Aksi"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 5.5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3zm0 5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3zm0 5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3z" fill="currentColor"/>
                                    </svg>
                                </button>

                                <div class="row-menu" id="menu-{{ $index }}">
                                    <a href="#">Lihat Detail</a>
                                    <a href="#">Edit Laporan</a>
                                    <a href="#" class="danger">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">Belum ada data laporan terbaru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="pagination">
            <button type="button">Sebelumnya</button>
            <button type="button" class="active">1</button>
            <button type="button">2</button>
            <button type="button">3</button>
            <button type="button">Selanjutnya</button>
        </footer>
    </section>
@endsection