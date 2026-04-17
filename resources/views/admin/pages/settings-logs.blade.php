@extends('admin.layouts.app')

@php
    $pageTitle = 'Log Aktivitas - Pengaturan - SiNemu';
    $activeMenu = 'settings';
    $hideSidebar = true;
    $hideSearch = true;
    $topbarBackUrl = route('admin.settings');
    $topbarBackLabel = 'Kembali ke Pengaturan';
@endphp

@section('page-content')
    <section class="settings-log-page">
<header class="settings-log-header">
            <h1>Log Aktivitas</h1>
            <p>Riwayat notifikasi dan perubahan yang tercatat untuk akun admin.</p>
        </header>

        <section class="settings-log-summary">
            <article>
                <span>Total</span>
                <strong>{{ $summary['total'] }}</strong>
                <small>Semua log yang tercatat untuk akun ini.</small>
            </article>
            <article>
                <span>Belum Dibaca</span>
                <strong>{{ $summary['unread'] }}</strong>
                <small>Perlu ditinjau agar aktivitas terbaru tidak terlewat.</small>
            </article>
            <article>
                <span>Sudah Dibaca</span>
                <strong>{{ $summary['read'] }}</strong>
                <small>Aktivitas yang sudah dikonfirmasi oleh admin.</small>
            </article>
        </section>

        <section class="report-card settings-log-card">
            <header>
                <form class="settings-log-toolbar" method="GET" action="{{ route('admin.settings.logs') }}">
                    <div class="settings-log-toolbar-left">
                        <select name="status" class="filter-btn">
                            <option value="" @selected($statusFilter === '')>Semua Status</option>
                            <option value="unread" @selected($statusFilter === 'unread')>Belum Dibaca</option>
                            <option value="read" @selected($statusFilter === 'read')>Sudah Dibaca</option>
                        </select>
                        <select name="type" class="filter-btn">
                            <option value="" @selected($typeFilter === '')>Semua Tipe</option>
                            @foreach($typeOptions as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected($typeFilter === $typeValue)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="date" class="filter-btn" value="{{ $dateFilter }}">
                    </div>

                    <div class="settings-log-toolbar-right">
                        <input type="text" name="search" class="filter-btn settings-log-search" placeholder="Cari judul/pesan..." value="{{ $search }}">
                        <button type="submit" class="filter-btn">Filter</button>
                        @if($statusFilter !== '' || $typeFilter !== '' || $dateFilter !== '' || $search !== '')
                            <a href="{{ route('admin.settings.logs') }}" class="filter-btn">Reset</a>
                        @endif
                    </div>
                </form>
            </header>

            <div class="settings-log-toolbar-meta">
                <p class="settings-log-toolbar-note">
                    {{ $summary['total'] > 0 ? 'Gunakan filter untuk mempercepat pencarian log aktivitas.' : 'Log akan muncul otomatis setelah ada notifikasi atau aksi admin.' }}
                </p>
            </div>

            <div class="settings-log-list">
                @forelse($logs as $log)
                    @php
                        $typeLabel = str_replace('_', ' ', ucwords((string) $log->type, '_'));
                        $isUnread = is_null($log->read_at);
                        $badgeClass = $isUnread ? 'is-unread' : 'is-read';
                    @endphp
                    <article class="settings-log-item {{ $badgeClass }}">
                        <div class="settings-log-item-main">
                            <div class="settings-log-item-head">
                                <strong>{{ $log->title }}</strong>
                                <span class="settings-log-type">{{ $typeLabel }}</span>
                            </div>
                            <p>{{ $log->message }}</p>
                            <small>
                                {{ $log->created_at?->translatedFormat('d M Y, H:i') }} WIB
                                @if($isUnread)
                                    <span class="status-chip status-dalam_peninjauan">Belum Dibaca</span>
                                @else
                                    <span class="status-chip status-selesai">Sudah Dibaca</span>
                                @endif
                            </small>
                        </div>

                        <div class="settings-log-item-actions">
                            @if(!empty($log->action_url))
                                <a href="{{ $log->action_url }}" class="filter-btn">Buka</a>
                            @endif

                            @if($isUnread)
                                <form method="POST" action="{{ route('admin.notifications.read', $log->id) }}">
                                    @csrf
                                    <button type="submit" class="filter-btn">Tandai Dibaca</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.notifications.destroy', $log->id) }}" data-confirm-delete data-confirm-message="Hapus log ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="filter-btn danger">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="settings-log-empty">
                        <iconify-icon icon="mdi:clipboard-text-clock-outline" aria-hidden="true"></iconify-icon>
                        <strong>Belum ada log aktivitas</strong>
                        <p>Riwayat akan tampil di sini setelah sistem mencatat notifikasi atau perubahan data.</p>
                    </div>
                @endforelse
            </div>

            @if($logs->hasPages())
                <footer class="pagination">
                    @if($logs->onFirstPage())
                        <button type="button" disabled>Sebelumnya</button>
                    @else
                        <button type="button" onclick="window.location.href='{{ $logs->previousPageUrl() }}'">Sebelumnya</button>
                    @endif

                    @for($page = 1; $page <= $logs->lastPage(); $page++)
                        <button type="button" class="{{ $logs->currentPage() === $page ? 'active' : '' }}" onclick="window.location.href='{{ $logs->url($page) }}'">{{ $page }}</button>
                    @endfor

                    @if($logs->hasMorePages())
                        <button type="button" onclick="window.location.href='{{ $logs->nextPageUrl() }}'">Selanjutnya</button>
                    @else
                        <button type="button" disabled>Selanjutnya</button>
                    @endif
                </footer>
            @endif
        </section>
    </section>
@endsection
