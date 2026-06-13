@props(['status' => null, 'expired' => false, 'variant' => null, 'icon' => null, 'size' => null])

@php
    $size = $size ?? 'md';
    $variant = $variant ?? match (true) {
        $expired ?? false => 'error',
        $status === true || $status === 'active' || $status === 'aktif' => 'success',
        $status === false || $status === 'inactive' || $status === 'nonaktif' => 'neutral',
        $status === 'pending' || $status === 'menunggu' => 'warning',
        default => 'neutral',
    };
    $icon = $icon ?? match ($variant) {
        'success' => 'check-circle',
        'error' => 'x-circle',
        'warning' => 'exclamation',
        default => 'x-circle',
    };
@endphp
<x-ui.badge :variant="$variant" :icon="$icon" :size="$size">{{ $slot }}</x-ui.badge>
