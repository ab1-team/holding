@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'placeholder' => null, 'required' => false, 'hint' => null, 'leadingIcon' => null, 'trailingIcon' => null, 'min' => null, 'max' => null, 'step' => null, 'clearable' => false])

@php
    $isPassword = $type === 'password';
    $isDatepicker = $type === 'date';
    $isMonthpicker = $type === 'month';
    $isPicker = $isDatepicker || $isMonthpicker;
    $hasLeading = $leadingIcon || $type === 'url';
    $hasTrailing = $trailingIcon || $isPassword || $isPicker;
    $baseInput = 'block w-full rounded-lg border bg-surface-container-lowest text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:outline-none transition disabled:opacity-50 ';
    $stateClass = $hasError()
        ? ' border-error focus:border-error focus:ring-error/30'
        : ' border-outline focus:border-primary focus:ring-primary/30';
    $padding = ($hasLeading ? 'pl-10 ' : 'pl-4 ') . ($hasTrailing ? 'pr-10 ' : 'pr-4 ');
    $hPadding = '';
@endphp

<div {{ $attributes->except('class') }}>
    @if($label)
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-on-surface">
        {{ $label }}
        @if($required)<span class="text-error">*</span>@endif
    </label>
    @endif

    <div class="relative" x-data="{ shown: false }">
        @if($hasLeading)
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant">
            <x-ui.icon :name="$leadingIcon ?: ($isDatepicker ? 'calendar' : 'link')" class="h-5 w-5" />
        </span>
        @endif

        <input
            type="{{ $isPassword ? 'password' : ($isPicker ? 'text' : $type) }}"
            name="{{ $name }}"
            id="{{ $name }}"
            @if($isDatepicker) data-datepicker @endif
            @if($isMonthpicker) data-monthpicker @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($resolvedValue() !== null) value="{{ $resolvedValue() }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            @if(isset($wireModel)) wire:model.live.debounce.300ms="{{ $wireModel }}" @endif
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.live.debounce.300ms'])->merge(['class' => $baseInput . $padding . $stateClass . ' h-10 ' . $hPadding]) }}
        >

        @if($isPassword)
        <button type="button" @click="$el.previousElementSibling.type = $el.previousElementSibling.type === 'password' ? 'text' : 'password'; $el.querySelectorAll('svg').forEach(s => s.classList.toggle('hidden'))" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
            <x-ui.icon name="eye" class="h-5 w-5" />
            <x-ui.icon name="eye-slash" class="hidden h-5 w-5" />
        </button>
        @elseif($isPicker)
        <button type="button" tabindex="-1" data-picker-trigger class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface" @click="$el.previousElementSibling.focus(); if (window.flatpickr) { const fp = $el.previousElementSibling._flatpickr; if (fp && !fp.isOpen) fp.open(); }">
            <x-ui.icon name="calendar" class="h-5 w-5" />
        </button>
        @elseif($clearable)
        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface"
                {{ $attributes->filter(fn ($v, $k) => str_starts_with($k, 'wire:click') || $k === 'x-on:click' || str_starts_with($k, '@click'))->merge([]) }}>
            <x-ui.icon name="x-mark" class="h-5 w-5" />
        </button>
        @elseif($trailingIcon)
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant">
            <x-ui.icon :name="$trailingIcon" class="h-5 w-5" />
        </span>
        @endif
    </div>

    @if($hint && !$hasError())
    <p class="mt-1.5 text-xs text-on-surface-variant">{!! $hint !!}</p>
    @endif
    @if($hasError())
    <p class="mt-1.5 text-xs text-error">{{ $errorMessage() }}</p>
    @endif
</div>
