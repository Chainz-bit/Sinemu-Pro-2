@props([
    'id' => 'dashboard-sidebar',
    'activeMenu' => '',
    'brandUrl' => '/',
    'brandImage' => asset('img/logo.png'),
    'brandAlt' => 'SiNemu',
    'homeUrl' => null,
    'homeLabel' => 'Kembali ke Landing Page',
    'homeIcon' => 'mdi:home-outline',
    'navItems' => [],
])

<aside class="sidebar" id="{{ $id }}">
    {{-- BAGIAN: Header logo + shortcut ke landing page --}}
    <div class="sidebar-brand-row">
        @if(!empty($brandImage))
            <a class="sidebar-brand" href="{{ $brandUrl }}" aria-label="{{ $brandAlt }}">
                <img src="{{ $brandImage }}" alt="{{ $brandAlt }}">
            </a>
        @endif

        @if(!empty($homeUrl))
            <a
                href="{{ $homeUrl }}"
                class="sidebar-home-icon"
                aria-label="{{ $homeLabel }}"
                data-tooltip="{{ $homeLabel }}"
                title="{{ $homeLabel }}"
            >
                <iconify-icon icon="{{ $homeIcon }}"></iconify-icon>
            </a>
        @endif
    </div>

    {{-- BAGIAN: Navigasi utama --}}
    <nav class="sidebar-nav">
        @foreach($navItems as $item)
            @php
                $itemKey = (string) ($item['key'] ?? '');
                $isActive = $activeMenu === $itemKey;
                $iconName = trim((string) ($item['icon'] ?? ''));
            @endphp
            <a href="{{ $item['url'] ?? '#' }}" class="{{ $isActive ? 'active' : '' }}">
                @if($iconName !== '')
                    <iconify-icon icon="{{ $iconName }}"></iconify-icon>
                @endif
                <span>{{ $item['label'] ?? 'Menu' }}</span>
            </a>
        @endforeach
    </nav>

    {{-- BAGIAN: Area bawah sidebar (profil / aksi tambahan) --}}
    @if(trim((string) $slot) !== '')
        <div class="sidebar-bottom">
            {{ $slot }}
        </div>
    @endif
</aside>
