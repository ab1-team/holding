@php
    $isPassword = $type === 'password';
    $hasLeading = $leadingIcon || $type === 'url';
    $hasTrailing = $trailingIcon || $isPassword;
    $baseInput = 'block w-full rounded-lg border bg-surface-container-lowest text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:outline-none transition disabled:opacity-50';
    $stateClass = $hasError()
        ? ' border-error focus:border-error focus:ring-error/30'
        : ' border-outline focus:border-primary focus:ring-primary/30';
    $padding = ($hasLeading ? 'pl-10 ' : '') . ($hasTrailing ? 'pr-10 ' : '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-on-surface">
        {{ $label }}
        @if($required)<span class="text-error">*</span>@endif
    </label>
    @endif

    <div class="relative" x-data="{ shown: false }">
        @if($hasLeading)
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant">
            <x-ui.icon :name="$leadingIcon ?: 'link'" class="h-5 w-5" />
        </span>
        @endif

        <input
            type="{{ $isPassword ? (isset($shown) && false ? 'text' : 'password') : $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($resolvedValue() !== null) value="{{ $resolvedValue() }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            {{ $attributes->except('class')->merge(['class' => $baseInput . $padding . $stateClass . ' h-10 px-3.5']) }}
        >

        @if($isPassword)
        <button type="button" @click="$el.previousElementSibling.type = $el.previousElementSibling.type === 'password' ? 'text' : 'password'; $el.querySelector('svg').classList.toggle('hidden')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
            <x-ui.icon name="eye" class="h-5 w-5" />
            <x-ui.icon name="eye-slash" class="hidden h-5 w-5" />
        </button>
        @elseif($trailingIcon)
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant">
            <x-ui.icon :name="$trailingIcon" class="h-5 w-5" />
        </span>
        @endif
    </div>

    @if($hint && !$hasError())
    <p class="mt-1.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    @if($hasError())
    <p class="mt-1.5 text-xs text-error">{{ $errorMessage() }}</p>
    @endif
</div>
