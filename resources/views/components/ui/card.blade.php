@php
    $hasHeader = $title || $subtitle || $overline || trim($header ?? '');
@endphp
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated']) }}>
    @if($hasHeader)
    <div class="flex items-start justify-between gap-3 border-b border-outline-variant {{ $padded ? 'px-5 py-4 sm:px-6' : 'px-0 py-0' }}">
        <div class="min-w-0">
            @if($overline)
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ $overline }}</p>
            @endif
            @if($title)
            <h2 class="mt-0.5 text-base font-semibold text-on-surface">{{ $title }}</h2>
            @endif
            @if($subtitle)
            <p class="mt-0.5 text-sm text-on-surface-variant">{{ $subtitle }}</p>
            @endif
        </div>
        @if(trim($header ?? ''))
        <div class="shrink-0">{{ $header }}</div>
        @endif
    </div>
    @endif
    <div class="{{ $padded ? 'p-5 sm:p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
