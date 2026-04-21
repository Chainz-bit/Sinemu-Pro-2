<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Super Admin - SiNemu' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
    @vite('resources/js/entries/admin.js')
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js" defer></script>
</head>
<body>
    @php
        $sinemuFlashMessages = [];
        $statusMessage = session('status');
        if (!empty($statusMessage)) {
            $sinemuFlashMessages[] = ['type' => 'success', 'message' => (string) $statusMessage];
        }
        $errorMessage = session('error');
        if (!empty($errorMessage)) {
            $sinemuFlashMessages[] = ['type' => 'error', 'message' => (string) $errorMessage];
        }
        if ($errors->any()) {
            $sinemuFlashMessages[] = ['type' => 'error', 'message' => (string) $errors->first()];
        }
    @endphp
    <script>window.__SINEMU_FLASH_MESSAGES = @json($sinemuFlashMessages);</script>

    @php
        $hideSuperSidebar = (bool) ($hideSuperSidebar ?? false);
        $hideSuperSearch = (bool) ($hideSuperSearch ?? false);
    @endphp

    <div class="admin-shell {{ $hideSuperSidebar ? 'admin-shell-no-sidebar' : '' }}">
        @unless($hideSuperSidebar)
            @include('super.partials.sidebar', [
                'activeMenu' => $activeMenu ?? null,
                'superAdmin' => $superAdmin ?? null,
            ])

            <button type="button" class="sidebar-backdrop" aria-label="Tutup menu"></button>
        @endunless

        <main class="main-content">
            @include('super.partials.topbar', [
                'searchAction' => $searchAction ?? request()->url(),
                'searchPlaceholder' => $searchPlaceholder ?? 'Cari kecamatan atau instansi',
                'topbarBackUrl' => $topbarBackUrl ?? null,
                'topbarBackLabel' => $topbarBackLabel ?? 'Kembali',
                'activeMenu' => $activeMenu ?? null,
                'hideSidebarToggle' => $hideSuperSidebar,
                'hideSearch' => $hideSuperSearch,
            ])

            <div class="main-content-scroll">
                @yield('page-content')
            </div>
        </main>
    </div>
</body>
</html>
