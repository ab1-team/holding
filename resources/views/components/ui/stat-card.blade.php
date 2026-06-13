@props([
    'label',
    'value',
    'icon' => null,
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-primary-container text-on-primary-container',
        'secondary' => 'bg-secondary-container text-on-secondary-container',
        'tertiary' => 'bg-tertiary-container text-on-tertiary-container',
        'surface' => 'bg-surface-container text-on-surface-variant',
        default => 'bg-primary-container text-on-primary-container',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-2xl bg-surface-container-lowest p-5 shadow-elevated']) }}>
    @if($icon)
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $variantClasses }}">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </div>
    @endif
    <div class="min-w-0">
        <p class="text-xs font-medium text-on-surface-variant">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-semibold text-on-surface">{{ $value }}</p>
    </div>
</div>
