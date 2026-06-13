@props([
    'overline' => null,
    'title' => null,
    'subtitle' => null,
    'centered' => false,
])

@php
    $hasActions = trim($actions ?? '') !== '';
    $align = $centered ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between';
@endphp

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 ' . $align]) }}>
    <div class="min-w-0 {{ $centered ? '' : 'flex-1' }}">
        @if($overline)
        <p class="text-xs font-semibold uppercase tracking-wider text-secondary">{{ $overline }}</p>
        @endif
        @if($title)
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">{{ $title }}</h1>
        @endif
        @if($subtitle)
        <p class="mt-1 text-sm text-on-surface-variant">{!! $subtitle !!}</p>
        @endif
    </div>
    @if($hasActions)
    <div class="flex shrink-0 flex-wrap items-center gap-2 {{ $centered ? 'justify-center' : '' }}">{{ $actions }}</div>
    @endif
</div>
