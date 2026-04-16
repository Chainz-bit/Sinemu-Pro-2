<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Dashboard User - SiNemu' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">

    {{-- BAGIAN: Style reusable dashboard --}}
    <link rel="stylesheet" href="{{ asset('css/page-transition.css') }}?v={{ @filemtime(public_path('css/page-transition.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/user/app.css') }}?v={{ @filemtime(public_path('css/user/app.css')) }}">
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>
</head>
<body>
    {{-- BAGIAN: Kerangka dashboard user --}}
    <div class="admin-shell {{ ($hideSidebar ?? false) ? 'admin-shell-no-sidebar' : '' }}">
        @if(!($hideSidebar ?? false))
            @include('user.partials.sidebar', [
                'activeMenu' => $activeMenu ?? null,
                'user' => $user ?? auth()->user(),
            ])
            <button type="button" class="sidebar-backdrop" aria-label="Tutup menu"></button>
        @endif

        <main class="main-content">
            @include('user.partials.topbar', [
                'searchAction' => $searchAction ?? request()->url(),
                'searchPlaceholder' => $searchPlaceholder ?? 'Cari laporan Anda',
                'hideSidebar' => $hideSidebar ?? false,
                'hideTopActions' => $hideTopActions ?? false,
                'topbarBackUrl' => $topbarBackUrl ?? null,
                'topbarBackLabel' => $topbarBackLabel ?? 'Kembali',
                'activeMenu' => $activeMenu ?? null,
            ])

            @yield('page-content')
        </main>
    </div>

    {{-- BAGIAN: Script interaksi halaman dashboard user --}}
    <script src="{{ asset('js/page-transition.js') }}?v={{ @filemtime(public_path('js/page-transition.js')) }}" defer></script>
    <script type="module" src="{{ asset('js/user/app.js') }}?v={{ @filemtime(public_path('js/user/app.js')) }}"></script>
</body>
</html>
