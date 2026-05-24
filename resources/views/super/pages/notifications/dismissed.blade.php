@extends('super.layouts.app')

@php
    $pageTitle = 'Riwayat Notifikasi Disembunyikan - SiNemu';
    $activeMenu = 'settings';
    $hideSuperSidebar = true;
    $hideSuperSearch = true;
    $topbarBackUrl = route('super.dashboard');
    $topbarBackLabel = 'Kembali ke Dashboard';
@endphp

@section('page-content')
    <section class="settings-log-page">
        <header class="settings-log-header">
            <h1>Riwayat Notifikasi Disembunyikan</h1>
            <p>Activity yang disembunyikan dari topbar Super Admin.</p>
        </header>

        <section class="report-card settings-log-card">
            <header>
                <div class="settings-log-toolbar">
                    <div class="settings-log-toolbar-left">
                        <a href="{{ route('super.dashboard') }}" class="filter-btn">Kembali</a>
                    </div>

                    @if($dismissals->total() > 0)
                        <div class="settings-log-toolbar-right">
                            <form method="POST" action="{{ route('super.notifications.dismissed.clear') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="filter-btn">Tampilkan Semua</button>
                            </form>
                        </div>
                    @endif
                </div>
            </header>

            <div class="settings-log-toolbar-meta">
                <p class="settings-log-toolbar-note">
                    Menghapus item dari daftar ini hanya menampilkan kembali activity di topbar. Data pengelola tidak dihapus atau diubah.
                </p>
            </div>

            <div class="settings-log-list">
                @forelse($dismissals as $dismissal)
                    <article class="settings-log-item is-read">
                        <div class="settings-log-item-main">
                            <div class="settings-log-item-head">
                                <strong>{{ $dismissal->item_key }}</strong>
                                <span class="settings-log-type">Disembunyikan</span>
                            </div>
                            <p>Riwayat topbar Super Admin yang sedang disembunyikan.</p>
                            <small>
                                {{ $dismissal->dismissed_at?->translatedFormat('d M Y, H:i') ?? $dismissal->created_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                            </small>
                        </div>

                        <div class="settings-log-item-actions">
                            <form method="POST" action="{{ route('super.notifications.dismissed.destroy', $dismissal) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="filter-btn">Tampilkan Lagi</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="settings-log-empty">
                        <iconify-icon icon="mdi:bell-check-outline" aria-hidden="true"></iconify-icon>
                        <strong>Belum ada riwayat notifikasi yang disembunyikan.</strong>
                        <p>Activity yang disembunyikan dari topbar akan tampil di sini.</p>
                    </div>
                @endforelse
            </div>

            @if($dismissals->hasPages())
                <footer class="pagination">
                    @if($dismissals->onFirstPage())
                        <button type="button" disabled>Sebelumnya</button>
                    @else
                        <button type="button" onclick="window.location.href='{{ $dismissals->previousPageUrl() }}'">Sebelumnya</button>
                    @endif

                    @for($page = 1; $page <= $dismissals->lastPage(); $page++)
                        <button type="button" class="{{ $dismissals->currentPage() === $page ? 'active' : '' }}" onclick="window.location.href='{{ $dismissals->url($page) }}'">{{ $page }}</button>
                    @endfor

                    @if($dismissals->hasMorePages())
                        <button type="button" onclick="window.location.href='{{ $dismissals->nextPageUrl() }}'">Selanjutnya</button>
                    @else
                        <button type="button" disabled>Selanjutnya</button>
                    @endif
                </footer>
            @endif
        </section>
    </section>
@endsection
