<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Admin - SiNemu' }}</title>

    {{-- BAGIAN: Gaya Global --}}
    <link rel="stylesheet" href="{{ asset('css/page-transition.css') }}?v={{ @filemtime(public_path('css/page-transition.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin/app.css') }}?v={{ @filemtime(public_path('css/admin/app.css')) }}">
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>
</head>
<body>
    {{-- BAGIAN: Kerangka Admin --}}
    <div class="admin-shell">
        {{-- BAGIAN: Sidebar --}}
        @include('admin.partials.sidebar', [
            'activeMenu' => $activeMenu ?? null,
            'admin' => $admin ?? null
        ])

        {{-- BAGIAN: Latar Belakang Mobile --}}
        <button type="button" class="sidebar-backdrop" aria-label="Tutup menu"></button>

        {{-- BAGIAN: Konten Utama --}}
        <main class="main-content">
            {{-- BAGIAN: Bilah Atas --}}
            @include('admin.partials.topbar', [
                'searchAction' => $searchAction ?? request()->url(),
                'searchPlaceholder' => $searchPlaceholder ?? 'Cari laporan atau barang...',
            ])

            {{-- BAGIAN: Konten Halaman --}}
            @yield('page-content')
        </main>
    </div>

    {{-- BAGIAN: Skrip Global --}}
    <script src="{{ asset('js/page-transition.js') }}?v={{ @filemtime(public_path('js/page-transition.js')) }}" defer></script>
    <script type="module" src="{{ asset('js/admin/app.js') }}?v={{ @filemtime(public_path('js/admin/app.js')) }}"></script>
</body>
</html>

