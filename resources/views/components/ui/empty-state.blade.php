@props([
    'icon' => 'inbox',
    'title' => null,
    'description' => null,
])

@php
    $hasAction = trim($action ?? '') !== '';
@endphp

<div {{ $attributes->merge(['class' => 'px-5 py-12 text-center sm:px-6']) }}>
    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface-container text-on-surface-variant">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </div>
    @if($title)
    <p class="text-sm font-medium text-on-surface">{{ $title }}</p>
    @endif
    @if($description)
    <p class="mt-1 text-xs text-on-surface-variant">{{ $description }}</p>
    @endif
    @if($hasAction)
    <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endif
</div>
