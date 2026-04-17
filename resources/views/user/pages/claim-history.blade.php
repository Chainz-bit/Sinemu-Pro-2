@extends('user.layouts.app')

@php
    $pageTitle = 'Riwayat Klaim Barang - User - SiNemu';
    $activeMenu = 'claim-history';
    $searchAction = route('user.claim-history');
    $searchPlaceholder = 'Cari riwayat klaim';
@endphp

@section('page-content')
    <div class="dashboard-page-content">
<section class="intro">
            <h1>Riwayat Klaim Barang</h1>
            <p>Pantau status pengajuan klaim barang temuan Anda di sini.</p>
        </section>

        <section class="report-card claim-status-guide">
            <header>
                <div class="report-heading">
                    <h2>Arti Status Klaim</h2>
                    <p>Gunakan panduan ini untuk mengetahui langkah Anda berikutnya.</p>
                </div>
            </header>
            <div class="claim-status-guide-body">
                <span class="status-chip status-dalam_peninjauan">Menunggu Tinjauan</span>
                <span class="status-chip status-diproses">Sedang Diproses</span>
                <span class="status-chip status-ditolak">Tidak Disetujui</span>
                <span class="status-chip status-selesai">Selesai</span>
            </div>
        </section>

        <section class="report-card report-card-scrollable dashboard-report-card claim-history-card">
            <header>
                <div class="report-heading">
                    <h2>Daftar Riwayat Klaim</h2>
                    <p>Menampilkan klaim terbaru berdasarkan aktivitas Anda.</p>
                </div>
                <div class="report-actions">
                    <form method="GET" action="{{ route('user.claim-history') }}" class="dashboard-filter-form claim-history-filter-form">
                        @if($search !== '')
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        @if($typeFilter !== '' && $typeFilter !== 'semua')
                            <input type="hidden" name="type" value="{{ $typeFilter }}">
                        @endif
                        <select name="status" class="filter-btn dashboard-filter-select" onchange="this.form.submit()">
                            <option value="semua" @selected($statusFilter === 'semua')>Semua Status</option>
                            <option value="menunggu_tinjauan" @selected($statusFilter === 'menunggu_tinjauan')>Menunggu Tinjauan</option>
                            <option value="sedang_diproses" @selected($statusFilter === 'sedang_diproses')>Sedang Diproses</option>
                            <option value="tidak_disetujui" @selected($statusFilter === 'tidak_disetujui')>Tidak Disetujui</option>
                            <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                        </select>
                    </form>
                    <form method="GET" action="{{ route('user.claim-history') }}" class="dashboard-filter-form claim-history-filter-form">
                        @if($search !== '')
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        @if($statusFilter !== '' && $statusFilter !== 'semua')
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                        @endif
                        <select name="type" class="filter-btn dashboard-filter-select" onchange="this.form.submit()">
                            <option value="semua" @selected($typeFilter === 'semua')>Semua Jenis</option>
                            <option value="temuan" @selected($typeFilter === 'temuan')>Barang Temuan</option>
                            <option value="hilang" @selected($typeFilter === 'hilang')>Laporan Hilang</option>
                        </select>
                    </form>
                </div>
            </header>

            <div class="report-table-wrap">
                <table class="report-table claim-history-table">
                    <colgroup>
                        <col class="col-item">
                        <col class="col-date">
                        <col class="col-status">
                        <col class="col-pickup">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Detail Barang</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Status</th>
                            <th>Lokasi Pengambilan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $claim)
                            <tr>
                                <td>
                                    <div class="item-cell">
                                        <div class="item-avatar avatar-claim-image">
                                            <img src="{{ $claim->item_image }}" alt="{{ $claim->item_name }}">
                                        </div>
                                        <div>
                                            <strong>{{ $claim->item_name }}</strong>
                                            <small>{{ $claim->item_type }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <strong>{{ $claim->submitted_at?->format('d M Y') }}</strong>
                                        <small>{{ $claim->submitted_at?->format('H:i') }} WIB</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-chip {{ $claim->status_class }}">
                                        {{ $claim->status_text }}
                                    </span>
                                    <small class="claim-status-detail">{{ $claim->status_detail }}</small>
                                </td>
                                <td>{{ $claim->pickup_location }}</td>
                                <td class="menu-cell">
                                    <button type="button"
                                            class="row-menu-trigger"
                                            data-menu-target="claim-history-menu-{{ $claim->id }}-{{ $loop->index }}"
                                            aria-label="Aksi">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.8" fill="currentColor"/>
                                            <circle cx="12" cy="12" r="1.8" fill="currentColor"/>
                                            <circle cx="12" cy="19" r="1.8" fill="currentColor"/>
                                        </svg>
                                    </button>

                                    <div class="row-menu" id="claim-history-menu-{{ $claim->id }}-{{ $loop->index }}">
                                        <a href="{{ $claim->detail_url }}">Lihat Detail</a>
                                        <form method="POST"
                                              action="{{ route('user.claim-history.destroy', ['klaim' => $claim->id, 'search' => $search, 'status' => $statusFilter, 'type' => $typeFilter, 'page' => $claims->currentPage()]) }}"
                                              data-confirm-delete
                                              data-confirm-message="Hapus riwayat klaim ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="menu-submit danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-row claim-history-empty-row">
                                    <strong>Belum ada riwayat klaim.</strong>
                                    <small>Coba ubah filter status atau kata kunci pencarian.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="pagination">
                @if($claims->onFirstPage())
                    <button type="button" disabled>Sebelumnya</button>
                @else
                    <button type="button" onclick="window.location.href='{{ $claims->previousPageUrl() }}'">Sebelumnya</button>
                @endif

                @for($page = 1; $page <= $claims->lastPage(); $page++)
                    <button type="button" class="{{ $claims->currentPage() === $page ? 'active' : '' }}" onclick="window.location.href='{{ $claims->url($page) }}'">{{ $page }}</button>
                @endfor

                @if($claims->hasMorePages())
                    <button type="button" onclick="window.location.href='{{ $claims->nextPageUrl() }}'">Selanjutnya</button>
                @else
                    <button type="button" disabled>Selanjutnya</button>
                @endif
            </footer>
        </section>
    </div>
@endsection
