@props([
    'icon' => 'mdi:information-outline',
    'title',
    'message' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->class(['claim-create-empty super-empty-panel']) }}>
    <iconify-icon icon="{{ $icon }}"></iconify-icon>
    <strong>{{ $title }}</strong>
    @if($message)
        <p>{!! $message !!}</p>
    @endif
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="super-inline-btn">{{ $actionLabel }}</a>
    @endif
</div>
