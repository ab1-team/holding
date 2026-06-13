@props(['icon' => 'cube', 'variant' => 'primary', 'name' => null, 'subtitle' => null, 'subtitleRaw' => null, 'nameRaw' => null])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-primary-container text-on-primary-container',
        'secondary' => 'bg-secondary-container text-on-secondary-container',
        'tertiary' => 'bg-tertiary-container text-on-tertiary-container',
        'surface' => 'bg-surface-container text-on-surface-variant',
        default => 'bg-primary-container text-on-primary-container',
    };
@endphp
<div class="flex items-center gap-3">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $variantClasses }}">
        <x-ui.icon :name="$icon" class="h-5 w-5" />
    </div>
    <div class="min-w-0">
        @if($nameRaw)
            <div class="truncate text-sm font-semibold text-on-surface">{!! $nameRaw !!}</div>
        @elseif($name)
            <div class="truncate text-sm font-semibold text-on-surface">{{ $name }}</div>
        @endif
        @if($subtitleRaw)
            <div class="truncate text-xs text-on-surface-variant">{!! $subtitleRaw !!}</div>
        @elseif($subtitle)
            <div class="truncate text-xs text-on-surface-variant">{{ $subtitle }}</div>
        @endif
    </div>
</div>
