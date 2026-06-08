@php
    $tag = $href ? 'a' : 'button';
    $baseClasses = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-50 disabled:cursor-not-allowed';
    $classes = $baseClasses . ' ' . $variantClasses() . ' ' . $sizeClasses();
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled && !$href) disabled @endif
    @if($disabled && $href) aria-disabled="true" @endif>
    @if($icon && $iconPosition === 'left')
        <x-ui.icon :name="$icon" :class="$iconSize()" />
    @endif
    {{ $slot }}
    @if($icon && $iconPosition === 'right')
        <x-ui.icon :name="$icon" :class="$iconSize()" />
    @endif
</{{ $tag }}>
