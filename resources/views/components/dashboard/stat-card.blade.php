@props([
    'as' => null,
    'href' => null,
    'label',
    'value' => 0,
    'description' => null,
    'icon' => null,
    'active' => false,
])

@php
    $tag = $as ?? ($href ? 'a' : 'article');
@endphp

<{{ $tag }}
    {{ $attributes->class(['stat-card', 'is-active' => $active]) }}
    @if($href) href="{{ $href }}" @endif
>
    <div class="stat-card-head">
        <span>{{ $label }}</span>
        @if($icon)
            <div class="stat-card-icon">
                <iconify-icon icon="{{ $icon }}"></iconify-icon>
            </div>
        @endif
    </div>
    <strong>{{ $value }}</strong>
    @if($description)
        <small>{{ $description }}</small>
    @endif
</{{ $tag }}>
